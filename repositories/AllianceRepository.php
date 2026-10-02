<?php

namespace Modules\Alliance\Repositories;

use App\Repositories\BaseRepository;

final class AllianceRepository extends BaseRepository
{
    public function findInfoById(int $id): array|false
    {
        return $this->preparedFetchOne(
            'SELECT ally_name,ally_tag,ally_description,ally_web,ally_image FROM {{table}} WHERE id = ?',
            array($id),
            'alliance'
        );
    }

    public function findFullById(int $id): array|false
    {
        return $this->preparedFetchOne('SELECT * FROM {{table}} WHERE `id` = ?', array($id), 'alliance');
    }

    public function countMembers(int $allyId): array|false
    {
        return $this->preparedFetchOne(
            "SELECT COUNT(DISTINCT(id)) AS n FROM {{table}} WHERE ally_id = ?",
            array($allyId),
            'users'
        );
    }

    /**
     * Préchargement de la vue galaxie.
     *
     * Une colonne d'alliance interrogeait la base deux fois par ligne (l'alliance
     * et son effectif) : deux requêtes suffisent pour tout un système.
     */
    private static ?array $prefetched = null;

    private static array $prefetchedMembers = array();

    /** @param int[] $ids */
    public function prefetch(array $ids): void
    {
        $ids = array_values(array_unique(array_filter(
            array_map('intval', $ids),
            static fn (int $id): bool => $id > 0
        )));

        self::$prefetched = array();
        self::$prefetchedMembers = array();
        if ($ids === array()) {
            return;
        }

        $placeholders = implode(', ', array_fill(0, count($ids), '?'));

        foreach ($this->preparedFetchAll('SELECT * FROM {{table}} WHERE id IN (' . $placeholders . ')', $ids, 'alliance') as $row) {
            self::$prefetched[(int) $row['id']] = $row;
        }

        foreach (
            $this->preparedFetchAll(
                'SELECT ally_id, COUNT(DISTINCT(id)) AS n FROM {{table}} WHERE ally_id IN (' . $placeholders . ') GROUP BY ally_id',
                $ids,
                'users'
            ) as $row
        ) {
            self::$prefetchedMembers[(int) $row['ally_id']] = (int) $row['n'];
        }
    }

    /** Ligne préchargée, ou null si le préchargement ne connaît pas cette alliance. */
    public function prefetchedById(int $id): ?array
    {
        if (self::$prefetched === null) {
            return null;
        }

        return self::$prefetched[$id] ?? null;
    }

    /** Effectif préchargé, ou null si aucun préchargement n'a eu lieu. */
    public function prefetchedMemberCount(int $id): ?int
    {
        if (self::$prefetched === null) {
            return null;
        }

        return self::$prefetchedMembers[$id] ?? 0;
    }

    public function searchByTag(string $term): array
    {
        return $this->preparedFetchAll(
            "SELECT * FROM {{table}} WHERE ally_tag LIKE ? LIMIT 30",
            array('%' . $term . '%'),
            'alliance'
        );
    }

    public function searchByName(string $term): array
    {
        return $this->preparedFetchAll(
            "SELECT * FROM {{table}} WHERE ally_name LIKE ? LIMIT 30",
            array('%' . $term . '%'),
            'alliance'
        );
    }

    public function findByTagFull(string $tag): array|false
    {
        return $this->preparedFetchOne('SELECT * FROM {{table}} WHERE ally_tag = ?', array($tag), 'alliance');
    }

    public function findByIdRaw(int $id): array|false
    {
        return $this->preparedFetchOne('SELECT * FROM {{table}} WHERE id = ?', array($id), 'alliance');
    }

    public function findTagAndRequestById(int $id): array|false
    {
        return $this->preparedFetchOne('SELECT ally_tag,ally_request FROM {{table}} WHERE id = ?', array($id), 'alliance');
    }

    public function findTagById(int $id): array|false
    {
        return $this->preparedFetchOne('SELECT ally_tag FROM {{table}} WHERE id = ? ORDER BY `id`', array($id), 'alliance');
    }

    public function insert(string $name, string $tag, int $ownerId, int $registerTime): void
    {
        $this->preparedExecute(
            'INSERT INTO {{table}} (ally_name, ally_tag, ally_owner, ally_owner_range, ally_members, ally_register_time)'
                . " VALUES (?, ?, ?, 'Leader', 1, ?)",
            array($name, $tag, $ownerId, $registerTime),
            'alliance'
        );
    }

    public function searchByNameOrTag(string $term): array
    {
        $like = '%' . $term . '%';

        return $this->preparedFetchAll(
            "SELECT * FROM {{table}} WHERE ally_name LIKE ? OR ally_tag LIKE ? LIMIT 30",
            array($like, $like),
            'alliance'
        );
    }

    public function updateUserAlly(int $userId, int $allyId, string $allyName, int $registerTime): void
    {
        $this->preparedExecute(
            'UPDATE {{table}} SET `ally_id` = ?, `ally_name` = ?, `ally_register_time` = ? WHERE `id` = ?',
            array($allyId, $allyName, $registerTime, $userId),
            'users'
        );
    }

    public function setUserRequest(int $userId, int $allyId, string $text, int $registerTime): void
    {
        $this->preparedExecute(
            'UPDATE {{table}} SET `ally_request` = ?, `ally_request_text` = ?, `ally_register_time` = ? WHERE `id` = ?',
            array($allyId, $text, $registerTime, $userId),
            'users'
        );
    }

    public function clearUserRequest(int $userId): void
    {
        $this->preparedExecute('UPDATE {{table}} SET `ally_request` = 0 WHERE `id` = ?', array($userId), 'users');
    }

    public function clearUserAlly(int $userId): void
    {
        $this->preparedExecute("UPDATE {{table}} SET `ally_id` = 0, `ally_name` = '' WHERE `id` = ?", array($userId), 'users');
    }

    public function resetMissingAlly(int $userId): void
    {
        $this->preparedExecute("UPDATE {{table}} SET `ally_name` = '', `ally_id` = 0 WHERE `id` = ?", array($userId), 'users');
    }

    public function updateMembersCount(int $allyId, int $count): void
    {
        $this->preparedExecute('UPDATE {{table}} SET `ally_members` = ? WHERE `id` = ?', array($count, $allyId), 'alliance');
    }

    public function listMembers(int $allyId, string $sort = ''): array
    {
        return $this->preparedFetchAll('SELECT * FROM {{table}} WHERE ally_id = ?' . $sort, array($allyId), 'users');
    }

    public function listMemberIds(int $allyId, int $rank): array
    {
        if ($rank == 0) {
            return $this->preparedFetchAll('SELECT id,username FROM {{table}} WHERE ally_id = ?', array($allyId), 'users');
        }

        return $this->preparedFetchAll('SELECT id,username FROM {{table}} WHERE ally_id = ? AND ally_rank_id = ?', array($allyId, $rank), 'users');
    }

    public function insertMessage(array $message): void
    {
        $this->preparedExecute(
            'INSERT INTO {{table}} (message_owner, message_sender, message_time, message_type, message_from, message_subject, message_text)'
                . " VALUES (?, ?, ?, 2, ?, ?, ?)",
            array($message['owner'], $message['sender'], $message['time'], $message['from'], $message['subject'], $message['text']),
            'messages'
        );
    }

    public function incrementMessages(int $allyId, int $rank): void
    {
        $where = array($allyId, $rank);

        $this->preparedExecute('UPDATE {{table}} SET `new_message` = new_message + 1 WHERE ally_id = ? AND ally_rank_id = ?', $where, 'users');
        $this->preparedExecute('UPDATE {{table}} SET `mnl_alliance` = mnl_alliance + 1 WHERE ally_id = ? AND ally_rank_id = ?', $where, 'users');
    }

    public function updateRanks(int $allyId, string $ranks): void
    {
        $this->preparedExecute('UPDATE {{table}} SET `ally_ranks` = ? WHERE `id` = ?', array($ranks, $allyId), 'alliance');
    }

    public function updateSettings(int $allyId, string $ownerRange, string $web, string $image, int $requestNotallow): void
    {
        $this->preparedExecute(
            'UPDATE {{table}} SET `ally_owner_range` = ?, `ally_image` = ?, `ally_web` = ?, `ally_request_notallow` = ? WHERE `id` = ?',
            array($ownerRange, $image, $web, $requestNotallow, $allyId),
            'alliance'
        );
    }

    /**
     * Écrit une description de l'alliance.
     *
     * `$field` est une colonne (validée ici), jamais une valeur ; le texte et
     * l'identifiant partent en paramètres.
     */
    public function updateText(int $allyId, string $field, string $text): void
    {
        if (!self::isColumnName($field)) {
            throw new \InvalidArgumentException('Colonne de description inconnue : ' . $field);
        }

        $this->preparedExecute(
            'UPDATE {{table}} SET `' . $field . '` = ? WHERE `id` = ?',
            array($text, $allyId),
            'alliance'
        );
    }

    public function transferOwnership(int $allyId, int $newOwnerId): void
    {
        $this->preparedExecute('UPDATE {{table}} SET `ally_owner` = ? WHERE `id` = ?', array($newOwnerId, $allyId), 'alliance');
    }

    public function findUserByIdRaw(int $id): array|false
    {
        return $this->preparedFetchOne('SELECT * FROM {{table}} WHERE id = ? LIMIT 1', array($id), 'users');
    }

    public function clearMemberAlly(int $userId): void
    {
        $this->preparedExecute("UPDATE {{table}} SET `ally_id` = 0, `ally_name` = '' WHERE `id` = ?", array($userId), 'users');
    }

    public function setUserRank(int $userId, string $rank): void
    {
        $this->preparedExecute('UPDATE {{table}} SET `ally_rank_id` = ? WHERE `id` = ?', array($rank, $userId), 'users');
    }

    public function findRequests(int $allyId): array
    {
        return $this->preparedFetchAll('SELECT id,username,ally_request_text,ally_register_time FROM {{table}} WHERE ally_request = ?', array($allyId), 'users');
    }

    public function findUserRequestRow(int $userId): array|false
    {
        return $this->preparedFetchOne('SELECT * FROM {{table}} WHERE id = ?', array($userId), 'users');
    }

    public function countRequests(int $allyId): int
    {
        $row = $this->preparedFetchOne(
            'SELECT COUNT(*) AS n FROM {{table}} WHERE ally_request = ?',
            array($allyId),
            'users'
        );

        return $row === false ? 0 : (int) $row['n'];
    }

    public function acceptRequest(int $allyId, int $allyIdField, string $allyName, int $show, string $text): void
    {
        $this->preparedExecute(
            'UPDATE {{table}} SET ally_members = ally_members + 1 WHERE id = ?',
            array($allyId),
            'alliance'
        );

        $this->preparedExecute(
            'UPDATE {{table}} SET `ally_name` = ?, `ally_request_text` = \'\', `ally_request` = \'0\', `ally_id` = ?, `new_message` = new_message + 1, `mnl_alliance` = mnl_alliance + 1 WHERE id = ?',
            array($allyName, $allyIdField, $show),
            'users'
        );

        $this->preparedExecute(
            'INSERT INTO {{table}} (message_owner, message_sender, message_time, message_type, message_from, message_subject, message_text)'
                . ' VALUES (?, ?, ?, 2, ?, ?, ?)',
            array(
                $show,
                $this->currentUserId(),
                time(),
                $this->currentAllyTag(),
                '[' . $this->currentAllyName() . '] vous a acceptee!',
                'Hi!<br>L\'Alliance <b>' . $this->currentAllyName() . '</b> a acceptee votre candidature!<br>Charte:<br>' . $text,
            ),
            'messages'
        );
    }

    public function refuseRequest(int $allyId, int $show, string $text): void
    {
        $this->preparedExecute(
            "UPDATE {{table}} SET `ally_request_text` = '', `ally_request` = '0', `ally_id` = '0', `new_message` = new_message + 1, `mnl_alliance` = mnl_alliance + 1 WHERE id = ?",
            array($show),
            'users'
        );

        $this->preparedExecute(
            'INSERT INTO {{table}} (message_owner, message_sender, message_time, message_type, message_from, message_subject, message_text)'
                . ' VALUES (?, ?, ?, 2, ?, ?, ?)',
            array(
                $show,
                $this->currentUserId(),
                time(),
                $this->currentAllyTag(),
                '[' . $this->currentAllyName() . '] vous as refuse!',
                'Hi!<br>L\'Alliance <b>' . $this->currentAllyName() . '</b> a refusee votre candidature!<br>Begr&uuml;ndung/Text:<br>' . $text,
            ),
            'messages'
        );
    }

    public function updateName(int $userId, string $name): void
    {
        $this->preparedExecute('UPDATE {{table}} SET `ally_name` = ? WHERE `id` = ?', array($name, $userId), 'alliance');
        $this->preparedExecute('UPDATE {{table}} SET `ally_name` = ? WHERE `ally_id` = ?', array($name, $userId), 'users');
    }

    public function updateTag(int $userId, string $tag): void
    {
        $this->preparedExecute('UPDATE {{table}} SET `ally_tag` = ? WHERE `id` = ?', array($tag, $userId), 'alliance');
    }

    public function delete(int $allyId): void
    {
        $this->preparedExecute('DELETE FROM {{table}} WHERE id = ?', array($allyId), 'alliance');
    }

    private ?int $ctxUserId = null;
    private ?string $ctxAllyTag = null;
    private ?string $ctxAllyName = null;

    public function setRequestContext(int $userId, string $allyTag, string $allyName): void
    {
        $this->ctxUserId = $userId;
        $this->ctxAllyTag = $allyTag;
        $this->ctxAllyName = $allyName;
    }

    private function currentUserId(): int
    {
        return $this->ctxUserId ?? 0;
    }

    private function currentAllyTag(): string
    {
        return $this->ctxAllyTag ?? '';
    }

    private function currentAllyName(): string
    {
        return $this->ctxAllyName ?? '';
    }
}
