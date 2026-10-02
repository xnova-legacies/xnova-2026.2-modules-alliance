<?php

declare(strict_types=1);

namespace Modules\Alliance\Services;

/**
 * Règles partagées des formulaires d'alliance.
 *
 * Attention : AllianceRepository construit ses requêtes par interpolation
 * (pas de requête préparée). Les valeurs passées doivent donc être nettoyées
 * ET échappées, comme le fait le contrôleur historique avec
 * strip_tags() + mysql_escape_string().
 */
final class AllianceService
{
    /** Longueur maximale d'un texte d'alliance (candidature, circulaire). */
    public const MAX_TEXT = 5000;

    /** Droits disponibles dans ally_ranks. */
    public const RIGHTS = array(
        'onlinestatus',
        'memberlist',
        'mails',
        'kick',
        'rechtehand',
        'delete',
        'bewerbungen',
        'bewerbungenbearbeiten',
        'administrieren',
    );

    /** Texte libre échappé pour l'interpolation SQL. Fonction pure. */
    public static function text(mixed $value): string
    {
        return addslashes(strip_tags(trim((string) $value)));
    }

    /** Texte d'alliance limité à MAX_TEXT caractères. Fonction pure. */
    public static function limited(mixed $value): string
    {
        return mb_substr(self::text($value), 0, self::MAX_TEXT);
    }

    /** Nom d'alliance échappé. Fonction pure. */
    public static function name(mixed $value): string
    {
        return self::text($value);
    }

    /** Tag d'alliance échappé. Fonction pure. */
    public static function tag(mixed $value): string
    {
        return self::text($value);
    }

    /** Numéro de rang utilisé par les listes du jeu (0 = tous les membres). */
    public static function rank(mixed $value): int
    {
        return max(0, (int) $value);
    }

    /**
     * Décision d'une demande d'adhésion : « accept », « refuse » ou null.
     * Le formulaire historique envoie les libellés « Accepter » / « Refuser ».
     * Fonction pure.
     */
    public static function decision(array $payload): ?string
    {
        $value = strtolower(trim((string) ($payload['decision'] ?? $payload['action'] ?? '')));

        if (in_array($value, array('accept', 'accepter'), true)) {
            return 'accept';
        }

        return in_array($value, array('refuse', 'refuser'), true) ? 'refuse' : null;
    }

    /**
     * Rangs de l'alliance : toujours un tableau. La colonne ally_ranks peut être
     * vide ou illisible, auquel cas unserialize() renvoie false (et count() sur
     * false est une erreur fatale en PHP 8). Fonction pure.
     */
    public static function ranks(mixed $serialized): array
    {
        $ranks = @unserialize((string) $serialized);

        return is_array($ranks) ? $ranks : array();
    }

    /**
     * Le joueur peut-il exercer ce droit ? Le fondateur peut tout.
     * Fonction pure (lit ally_ranks désérialisé).
     */
    public static function can(array $user, array $ally, string $right): bool
    {
        if ((int) ($ally['ally_owner'] ?? 0) === (int) ($user['id'] ?? 0)) {
            return true;
        }

        $ranks = self::ranks($ally['ally_ranks'] ?? '');

        if ($ranks === array()) {
            return false;
        }

        $index = (int) ($user['ally_rank_id'] ?? 0) - 1;

        return (int) ($ranks[$index][$right] ?? 0) === 1;
    }
}
