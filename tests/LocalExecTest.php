<?php
/*
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license MIT
 * @version 04.09.20 03:34:19
 */

/** @noinspection PhpMethodMayBeStaticInspection */
declare(strict_types = 1);
namespace dicr\tests;

use dicr\exec\ExecException;
use dicr\exec\LocalExec;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\base\InvalidConfigException;
use function gmdate;
use function trim;

/**
 * Class LocalExecTest
 */
class LocalExecTest extends TestCase
{
    /** @var string */
    public const CMD = 'date';

    /** @var string[] */
    public const ARGS = ['-u', '+%y%m%d'];

    /**
     * Exec.
     *
     * @return LocalExec
     * @throws InvalidConfigException
     */
    private static function exec() : LocalExec
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return Yii::$app->get('exec');
    }

    /**
     * @return string
     */
    private static function date() : string
    {
        return gmdate('ymd');
    }

    /**
     * @throws InvalidConfigException
     */
    public function testCreateCommand()
    {
        $exec = self::exec();
        self::assertSame("ps 'a' 'a b'", $exec::createCommand('ps', ['a', 'a b']));
    }

    /**
     * @throws InvalidConfigException
     * @throws ExecException
     */
    public function testExec()
    {
        $ret = self::exec()::exec(self::CMD, self::ARGS);
        self::assertSame(self::date(), trim($ret));
    }

    /**
     * @throws ExecException
     * @throws InvalidConfigException
     */
    public function testPassthru()
    {
        $ret = self::exec()::passthru(self::CMD, self::ARGS);
        self::assertSame(self::date(), trim($ret));
    }

    /**
     * @throws ExecException
     * @throws InvalidConfigException
     */
    public function testProcOpen()
    {
        $ret = self::exec()::procOpen(self::CMD, self::ARGS);
        self::assertSame(self::date(), trim($ret));
    }

    /**
     * @throws ExecException
     * @throws InvalidConfigException
     */
    public function testPopen()
    {
        $ret = self::exec()::popen(self::CMD, self::ARGS);
        self::assertSame(self::date(), trim($ret));
    }

    /**
     * @throws ExecException
     * @throws InvalidConfigException
     */
    public function testShellExec()
    {
        $ret = self::exec()::shellExec(self::CMD, self::ARGS);
        self::assertSame(self::date(), trim($ret));

    }

    /**
     * @throws ExecException
     * @throws InvalidConfigException
     */
    public function testSystem()
    {
        $ret = self::exec()::system(self::CMD, self::ARGS);
        self::assertSame(self::date(), trim($ret));
    }

    /**
     * @throws ExecException
     * @throws InvalidConfigException
     */
    public function testRun()
    {
        $ret = self::exec()->run(self::CMD, self::ARGS);
        self::assertSame(self::date(), trim($ret));
    }

}
