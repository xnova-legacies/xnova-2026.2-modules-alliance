<?php

declare(strict_types=1);

namespace Modules\Alliance\Controllers;

use App\Core\Api\ApiController;
use App\Core\Api\ApiException;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\UserRepository;
use Modules\Alliance\Repositories\AllianceRepository;
use Modules\Alliance\Services\AllianceService;

/**
 * Formulaires d'alliance (hors administration du panneau admin) :
 *
 *   POST /game/api/alliance/make      { aname, atag }
 *   POST /game/api/alliance/apply     { allyid, text }
 *   POST /game/api/alliance/circular  { r, text }
 *   POST /game/api/alliance/request   { id|show, decision, text }
 *   POST /game/api/alliance/rename    { field, value|newname|newtag }
 *   POST /game/api/alliance/leave     { confirm }
 *
 * Les droits sont ceux du jeu : le fondateur peut tout, un membre doit avoir le
 * droit correspondant dans son rang (ally_ranks).
 */
final class AllianceApiController extends ApiController
{
    public function __construct(
        private readonly AllianceRepository $alliances = new AllianceRepository(),
        private readonly UserRepository $users = new UserRepository(),
    ) {
    }

    public function makeAction(Request $request): Response
    {
        $user = $this->requireUser();
        $this->requireCsrf($request);
        $this->assertWithoutAlliance($user);

        $payload = $this->payload($request);
        $name = AllianceService::name($payload['aname'] ?? '');
        $tag = AllianceService::tag($payload['atag'] ?? '');

        if ($tag === '') {
            throw ApiException::validation('missing_tag', "Le tag de l'alliance est obligatoire.", array('atag' => 'Tag manquant.'));
        }

        if ($name === '') {
            throw ApiException::validation('missing_name', "Le nom de l'alliance est obligatoire.", array('aname' => 'Nom manquant.'));
        }

        if ($this->alliances->findByTagFull($tag)) {
            throw ApiException::validation('tag_taken', 'Ce tag est déjà utilisé.', array('atag' => 'Tag déjà utilisé.'));
        }

        $this->alliances->insert($name, $tag, (int) $user['id'], time());

        $ally = $this->alliances->findByTagFull($tag);

        if (is_array($ally)) {
            $this->alliances->updateUserAlly((int) $user['id'], (int) $ally['id'], (string) $ally['ally_name'], time());
        }

        return $this->success(
            array('tag' => $tag),
            array(),
            array(array('type' => 'success', 'text' => 'Alliance créée.'))
        );
    }

    public function applyAction(Request $request): Response
    {
        $user = $this->requireUser();
        $this->requireCsrf($request);
        $this->assertWithoutAlliance($user);

        $payload = $this->payload($request);
        $allyId = (int) ($payload['allyid'] ?? 0);

        if ($allyId <= 0 || !$this->alliances->findTagAndRequestById($allyId)) {
            throw ApiException::validation('unknown_alliance', 'Alliance inconnue.');
        }

        $text = AllianceService::limited($payload['text'] ?? '');

        $this->alliances->setUserRequest((int) $user['id'], $allyId, $text, time());

        return $this->success(
            array('allyid' => $allyId),
            array(),
            array(array('type' => 'success', 'text' => 'Candidature enregistrée.'))
        );
    }

    public function circularAction(Request $request): Response
    {
        $user = $this->requireUser();
        $this->requireCsrf($request);

        $ally = $this->requireAlly($user);

        if (!AllianceService::can($user, $ally, 'mails')) {
            throw ApiException::forbidden('not_allowed', "Vous n'avez pas le droit d'envoyer un message circulaire.");
        }

        $payload = $this->payload($request);
        $rank = AllianceService::rank($payload['r'] ?? 0);
        $text = AllianceService::limited($payload['text'] ?? '');

        if ($text === '') {
            throw ApiException::validation('missing_text', 'Le message est vide.', array('text' => 'Message manquant.'));
        }

        $sent = 0;

        foreach ($this->alliances->listMemberIds((int) $ally['id'], $rank) as $member) {
            $this->alliances->insertMessage(array(
                'owner' => $member['id'],
                'sender' => $user['id'],
                'time' => time(),
                // Le depot interpole ces valeurs : elles doivent etre echappees.
                'from' => addslashes((string) $ally['ally_tag']),
                'subject' => addslashes((string) $user['username']),
                'text' => $text,
            ));
            $sent++;
        }

        if ($sent > 0) {
            $this->alliances->incrementMessages((int) $ally['id'], $rank);
        }

        return $this->success(
            array('sent' => $sent),
            array(),
            array(array('type' => 'success', 'text' => 'Message envoyé à ' . $sent . ' membre(s).'))
        );
    }

    public function requestAction(Request $request): Response
    {
        $user = $this->requireUser();
        $this->requireCsrf($request);

        $ally = $this->requireAlly($user);

        if (!AllianceService::can($user, $ally, 'bewerbungenbearbeiten')) {
            throw ApiException::forbidden('not_allowed', 'Vous ne pouvez pas traiter les candidatures.');
        }

        $payload = $this->payload($request);
        $candidateId = (int) ($payload['id'] ?? $payload['show'] ?? 0);
        $decision = AllianceService::decision($payload);

        if ($candidateId <= 0) {
            throw ApiException::validation('unknown_candidate', 'Candidat inconnu.');
        }

        if ($decision === null) {
            throw ApiException::validation('invalid_decision', 'Merci de choisir accepter ou refuser.');
        }

        $text = AllianceService::limited($payload['text'] ?? '');

        if ($decision === 'accept') {
            $this->alliances->acceptRequest(
                (int) $ally['id'],
                (int) $ally['id'],
                addslashes((string) $ally['ally_name']),
                $candidateId,
                $text
            );
        } else {
            $this->alliances->refuseRequest((int) $ally['id'], $candidateId, $text);
        }

        return $this->success(
            array('decision' => $decision),
            array(),
            array(array('type' => 'success', 'text' => $decision === 'accept' ? 'Candidature acceptée.' : 'Candidature refusée.'))
        );
    }

    public function renameAction(Request $request): Response
    {
        $user = $this->requireUser();
        $this->requireCsrf($request);

        $ally = $this->requireAlly($user);

        if (!AllianceService::can($user, $ally, 'administrieren')) {
            throw ApiException::forbidden('not_allowed', "Vous n'avez pas le droit de modifier l'alliance.");
        }

        $payload = $this->payload($request);
        $field = strtolower(trim((string) ($payload['field'] ?? 'name')));
        $isTag = $field === 'tag';
        $value = (string) ($payload[$isTag ? 'newtag' : 'newname'] ?? $payload['value'] ?? '');

        if ($isTag) {
            $tag = AllianceService::tag($value);

            if ($tag === '') {
                throw ApiException::validation('missing_tag', 'Le tag est obligatoire.', array('newtag' => 'Tag manquant.'));
            }

            if ($this->alliances->findByTagFull($tag)) {
                throw ApiException::validation('tag_taken', 'Ce tag est déjà utilisé.', array('newtag' => 'Tag déjà utilisé.'));
            }

            $this->alliances->updateTag((int) $user['ally_id'], $tag);
        } else {
            $name = AllianceService::name($value);

            if ($name === '') {
                throw ApiException::validation('missing_name', "Le nom est obligatoire.", array('newname' => 'Nom manquant.'));
            }

            $this->alliances->updateName((int) $user['ally_id'], $name);
        }

        return $this->success(
            array('field' => $isTag ? 'tag' : 'name'),
            array(),
            array(array('type' => 'success', 'text' => "Alliance modifiée."))
        );
    }

    public function leaveAction(Request $request): Response
    {
        $user = $this->requireUser();
        $this->requireCsrf($request);

        $ally = $this->requireAlly($user);

        if ((int) $ally['ally_owner'] === (int) $user['id']) {
            throw ApiException::validation('owner_cannot_leave', "Le fondateur ne peut pas quitter l'alliance.");
        }

        if (AllianceService::can($user, $ally, 'delete') === false) {
            throw ApiException::forbidden('not_allowed', 'Vous ne pouvez pas quitter cette alliance.');
        }

        $payload = $this->payload($request);
        $confirm = in_array($payload['confirm'] ?? null, array('on', '1', 1, true), true);

        if (!$confirm) {
            throw ApiException::validation('missing_confirm', 'Merci de confirmer votre départ.', array('confirm' => 'Confirmation manquante.'));
        }

        $this->alliances->clearUserAlly((int) $user['id']);

        return $this->success(
            array(),
            array(),
            array(array('type' => 'success', 'text' => "Vous avez quitté l'alliance."))
        );
    }

    /** L'alliance du joueur, ou une erreur 422 s'il n'en a pas. */
    private function requireAlly(array $user): array
    {
        $ally = (int) ($user['ally_id'] ?? 0) > 0 ? $this->alliances->findFullById((int) $user['ally_id']) : false;

        if (!is_array($ally)) {
            throw ApiException::validation('no_alliance', "Vous n'êtes membre d'aucune alliance.");
        }

        return $ally;
    }

    /** Les formulaires de création et de candidature exigent un joueur libre. */
    private function assertWithoutAlliance(array $user): void
    {
        if ((int) ($user['ally_id'] ?? 0) > 0 || (int) ($user['ally_request'] ?? 0) > 0) {
            throw ApiException::validation('already_engaged', 'Vous appartenez déjà à une alliance ou avez une candidature en cours.');
        }
    }
}
