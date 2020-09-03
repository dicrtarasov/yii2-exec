<?php
/*
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license MIT
 * @version 03.09.20 22:15:52
 */

declare(strict_types = 1);
namespace dicr\exec;

/**
 * Исполнитель команд.
 */
interface ExecInterface
{
    /**
     * Выполняет внешнюю команду.
     *
     * @param string $cmd команда
     * @param array $args аргументы
     * @param array $options опции функции
     * - bool $escape - выполнить экранирование аргументов, false
     * @return string вывод команды
     * @throws ExecException
     */
    public function run(string $cmd, array $args = [], array $options = []) : string;
}
