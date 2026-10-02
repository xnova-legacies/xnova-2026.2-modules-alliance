<?php

declare(strict_types=1);

namespace Modules\Alliance\Tests;

use Modules\Alliance\Services\AllianceService;
use PHPUnit\Framework\TestCase;

final class AllianceServiceTest extends TestCase
{
    public function testTextRemovesTagsAndEscapesQuotes(): void
    {
        self::assertSame('Bonjour', AllianceService::text('  <b>Bonjour</b> '));
        self::assertSame("l\\'alliance", AllianceService::text("l'alliance"));
    }

    public function testLimitedTextIsCapped(): void
    {
        self::assertSame(5000, AllianceService::MAX_TEXT);
        self::assertSame(5000, mb_strlen(AllianceService::limited(str_repeat('a', 6000))));
    }

    public function testNamesAndTagsAreCleaned(): void
    {
        self::assertSame('Les Braves', AllianceService::name('<i>Les Braves</i>'));
        self::assertSame('TB', AllianceService::tag(' TB '));
    }

    public function testRankIsNeverNegative(): void
    {
        self::assertSame(0, AllianceService::rank('-2'));
        self::assertSame(3, AllianceService::rank('3'));
    }

    public function testDecisionReadsLegacyButtons(): void
    {
        self::assertSame('accept', AllianceService::decision(array('action' => 'Accepter')));
        self::assertSame('refuse', AllianceService::decision(array('action' => 'Refuser')));
        self::assertSame('accept', AllianceService::decision(array('decision' => 'ACCEPT')));
        self::assertNull(AllianceService::decision(array()));
        self::assertNull(AllianceService::decision(array('action' => 'peut-être')));
    }

    public function testRanksAlwaysReturnAnArray(): void
    {
        $ranks = array(array('name' => 'Chef', 'mails' => 1));

        self::assertSame($ranks, AllianceService::ranks(serialize($ranks)));
        self::assertSame(array(), AllianceService::ranks(''));
        self::assertSame(array(), AllianceService::ranks(null));
        self::assertSame(array(), AllianceService::ranks('pas-un-serialize'));
        self::assertSame(array(), AllianceService::ranks(serialize('chaine')));
    }

    public function testRightsCheck(): void
    {
        $ranks = array(array('mails' => 1, 'administrieren' => 0));
        $ally = array('ally_owner' => 7, 'ally_ranks' => serialize($ranks));
        $owner = array('id' => 7, 'ally_rank_id' => 5);
        $member = array('id' => 8, 'ally_rank_id' => 1);

        self::assertTrue(AllianceService::can($owner, $ally, 'administrieren'));
        self::assertTrue(AllianceService::can($member, $ally, 'mails'));
        self::assertFalse(AllianceService::can($member, $ally, 'administrieren'));
        self::assertFalse(AllianceService::can($member, array('ally_ranks' => 'pas-un-serialize'), 'mails'));
        self::assertSame(9, count(AllianceService::RIGHTS));
    }
}
