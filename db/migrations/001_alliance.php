<?php

declare(strict_types=1);

/**
 * Le schéma du **module alliance**.
 *
 * La table `alliance` était créée par `db/migrations/001_initial_schema.php`, dans
 * le Coeur d'application — avec `annonce`, `chat`, `buddy`, `notes`… Cette migration du module
 * ne la remplace pas : la migration 001 est **figée** (une migration appliquée ne se
 * modifie jamais) et reste la baseline historique du schéma.
 *
 * Elle sert à deux choses :
 *
 *  - **la propriété** : la table appartient au module, c'est écrit ici, et un test
 *    interdit désormais à une *nouvelle* migration du Coeur d'application de créer une table de
 *    module ;
 *  - **l'autonomie** : un jeu qui reçoit le module plus tard n'a peut-être pas la
 *    table — `IF NOT EXISTS` la crée alors, et ne fait rien si elle est déjà là.
 *
 * Le module étant optionnel, son schéma voyage avec lui.
 */

/**
 * L'alliance ne crée pas qu'une table : elle ajoute aussi **six colonnes** à
 * `users` — l'appartenance (`ally_id`), l'affichage (`ally_name`), la demande en
 * cours (`ally_request`, `ally_request_text`, `ally_register_time`) et le rang
 * (`ally_rank_id`). Elles aussi venaient de la migration 001 du Coeur d'application, qui reste la
 * baseline historique figée ; elles en sortent ici, parce qu'elles appartiennent au
 * module.
 *
 * **Une instruction par colonne** pour les `ALTER` : MySQL 5.7 n'a pas de
 * `ADD COLUMN IF NOT EXISTS`, et un `ALTER` groupé s'arrête à la première colonne
 * déjà présente. Le migrateur tolère 1060 (« duplicate column ») et 1091 (« column
 * not found »), donc le rejeu est inoffensif — sur un jeu ancien comme sur un jeu
 * qui reçoit le module plus tard.
 */
$colonnes = array(
    'ally_id' => "int(11) NOT NULL default '0'",
    'ally_name' => "varchar(32) default ''",
    'ally_request' => "int(11) NOT NULL default '0'",
    'ally_request_text' => 'mediumtext NULL',
    'ally_register_time' => "int(11) NOT NULL default '0'",
    'ally_rank_id' => "int(11) NOT NULL default '0'",
);

$up = array(
    array(
        'alliance',
        "CREATE TABLE IF NOT EXISTS `{{table}}` (
            `id` bigint(11) NOT NULL auto_increment,
            `ally_name` varchar(32) default '',
            `ally_tag` varchar(8) default '',
            `ally_owner` int(11) NOT NULL default '0',
            `ally_register_time` int(11) NOT NULL default '0',
            `ally_description` mediumtext NULL,
            `ally_web` varchar(255) default '',
            `ally_text` mediumtext NULL,
            `ally_image` varchar(255) default '',
            `ally_request` mediumtext NULL,
            `ally_request_waiting` mediumtext NULL,
            `ally_request_notallow` tinyint(4) NOT NULL default '0',
            `ally_owner_range` varchar(32) default '',
            `ally_ranks` mediumtext NULL,
            `ally_members` int(11) NOT NULL default '0',
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci",
    ),
);

$down = array(array('alliance', 'DROP TABLE {{table}}'));

foreach ($colonnes as $nom => $definition) {
    $up[] = array('users', 'ALTER TABLE {{table}} ADD COLUMN `' . $nom . '` ' . $definition);
    $down[] = array('users', 'ALTER TABLE {{table}} DROP COLUMN `' . $nom . '`');
}

return array(
    'up' => $up,
    'down' => $down,
);
