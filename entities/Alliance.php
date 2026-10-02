<?php

namespace Modules\Alliance\Entities;

use App\Entities\AbstractEntity;

final class Alliance extends AbstractEntity
{
    public static function fromRow(array $row): self
    {
        return new self($row);
    }

    public function id(): int
    {
        return (int) $this->raw('id');
    }

    public function name(): string
    {
        return (string) ($this->raw('ally_name') ?? '');
    }

    public function tag(): string
    {
        return (string) ($this->raw('ally_tag') ?? '');
    }

    public function ownerId(): int
    {
        return (int) $this->raw('ally_owner');
    }

    public function memberCount(): int
    {
        return (int) $this->raw('ally_members');
    }

    public function description(): string
    {
        return (string) ($this->raw('ally_description') ?? '');
    }

    public function web(): string
    {
        return (string) ($this->raw('ally_web') ?? '');
    }

    public function image(): string
    {
        return (string) ($this->raw('ally_image') ?? '');
    }

    public function requestText(): string
    {
        return (string) ($this->raw('ally_request') ?? '');
    }

    public function ranks(): array
    {
        // Colonne écrite par les formulaires d'alliance : aucune classe instanciée.
        $ranks = unserialize($this->raw('ally_ranks') ?? '', ['allowed_classes' => false]);

        return is_array($ranks) ? $ranks : [];
    }

    public function isOwner(int $userId): bool
    {
        return $this->ownerId() === $userId;
    }
}
