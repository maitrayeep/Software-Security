#!/usr/bin/php
<?php

abstract class Command {
	public abstract function help();
	public abstract function run();
}

class CommandsCommand extends Command {
	public function help() {
		global $argv;
		echo <<<TEXT

Usage: {$argv[0]} commands

Displays a list of all available commands. For help details on a specific
command, type:

{$argv[0]} help <command>


TEXT;
	}

	public function run() {
		$possible_commands = CommandFactory::getCommandKeys();
		$possible_commands_string = implode("\n", $possible_commands);
		echo <<<TEXT

Possible commands:

{$possible_commands_string}


TEXT;
	}
}

class HelpCommand extends Command {
	public function help() {
		global $argv;
		echo <<<TEXT

Need help with the help command? This help command is really helpful and quite
self explanatory really. You just type:

{$argv[0]} help <command>

where <command> is the command you'd like help on, then the help helps you in a
way that should be helpful. Hope this advice helpfully helps you with all the
help you need!

Good luck in PCTF!


TEXT;
	}

	public function run() {
		global $argc, $argv;
		if ($argc < 3) {
			echo <<<TEXT

No help available because command was not given.
Type '{$argv[0]} help <command>' to get the help you need.


TEXT;
			return;
		}
		$command = CommandFactory::getCommand($argv[2]);
		$command->help();
	}
}

class InvalidCommand extends Command {
	public function help() {
		global $argv;
		echo <<<TEXT

No help available because command is invalid.
Type '{$argv[0]} commands' for a list of available commands.


TEXT;
	}

	public function run() {
		global $argv;
		echo <<<TEXT

Invalid command.
Type '{$argv[0]} commands' for a list of available commands.


TEXT;
	}
}

class SetCommand extends Command {
	public function help() {
		global $argv;
		echo <<<TEXT

Usage: {$argv[0]} set <page> <type> <variable> <rule-id>
Example: {$argv[0]} set /index.php get msg 1

<page> is the requested page. If, for instance, <page> is "/index.php", the new
rule will apply only for requests to "/index.php".

<type> can be either "cookie" (\$_COOKIE), "env" (\$_ENV), "files" (\$_FILES),
"get" (\$_GET), "post" (\$_POST), "request" (\$_REQUEST), "server" (\$_SERVER),
or "session" (\$_SESSION).

<variable> is the variable name. If <type> is "get" and <variable> is "msg",
the new rule will apply to \$_GET['msg'].

<rule-id> is the id of the rule to apply.


TEXT;
	}

	public function run() {
		global $argc, $argv;
		if ($argc < 6) {
			echo <<<TEXT

Not enough arguments.
Type '{$argv[0]} help set' for details on how to use the set command.


TEXT;
			return;
		}
		Utilities::checkFilterJsonAccessibility();
		$page = $argv[2];
		$type = $argv[3];
		$variable = $argv[4];
		$rule_id = intval($argv[5]);

		$json = file_get_contents(Utilities::FILTER_JSON);
		$array = json_decode($json, true);
		$array[$page][$type][$variable] = $rule_id;
		$new_json = json_encode($array, JSON_PRETTY_PRINT);
		file_put_contents(Utilities::FILTER_JSON, $new_json."\n");
	}
}

class UnsetCommand extends Command {
	public function help() {
		global $argv;
		echo <<<TEXT

Usage: {$argv[0]} unset <page> [<type> <variable>]
Example: {$argv[0]} unset index.php get msg

<page> is the requested page. If, for instance, <page> is "/index.php", the rule
will no longer apply for requests to "/index.php".

<type> can be either "cookie" (\$_COOKIE), "env" (\$_ENV), "files" (\$_FILES),
"get" (\$_GET), "post" (\$_POST), "request" (\$_REQUEST), "server" (\$_SERVER),
or "session" (\$_SESSION).

<variable> is the name of the variable to be unset. If <type> is "get" and
<variable> is "msg", the rule for \$_GET['msg'] will be removed.

If <variable> is omitted, every variable of <type> for the given <page> will
be unset. If <type> is omitted, every filter for <page> will be unset.


TEXT;
	}

	public function run() {
		global $argc, $argv;
		if ($argc < 3) {
			echo <<<TEXT

Not enough arguments.
Type '{$argv[0]} help unset' for details on how to use the unset command.


TEXT;
			return;
		}
		$page = $argv[2];

		$json = file_get_contents(Utilities::FILTER_JSON);
		$array = json_decode($json, true);
		if ($argc >= 5) {
			$type = $argv[3];
			$variable = $argv[4];
			unset($array[$page][$type][$variable]);
		} elseif ($argc === 4) {
			$type = $argv[3];
			unset($array[$page][$type]);
		} else {
			// argc should be 3.
			unset($array[$page]);
		}
		$new_json = json_encode($array, JSON_PRETTY_PRINT);
		file_put_contents(Utilities::FILTER_JSON, $new_json."\n");
	}
}

abstract class CommandFactory {
	private static $command_map;
	private static $default_command;

	public static function getCommand($command_string) {
		$command_map = self::getCommandMap();
		if (array_key_exists($command_string, $command_map)) {
			return self::$command_map[$command_string];
		}
		return self::getDefaultCommand();
	}

	public static function getCommandKeys() {
		$command_map = self::getCommandMap();
		return array_keys($command_map);
	}

	private static function getCommandMap() {
		if (!self::$command_map) {
			self::$command_map = self::initCommandMap();
		}
		return self::$command_map;
	}

	private static function getDefaultCommand() {
		if (!self::$default_command) {
			self::$default_command = self::initDefaultCommand();
		}
		return self::$default_command;
	}

	private static function initCommandMap() {
		return [
			'commands' => new CommandsCommand,
			'help' => new HelpCommand,
			'set' => new SetCommand,
			'unset' => new UnsetCommand
		];
	}

	private static function initDefaultCommand() {
		return new InvalidCommand;
	}
}

abstract class Utilities {
	/**
	 * Location of filter.json file.
	 */
	const FILTER_JSON = "filter.json";

	public static function printUsageAndExit() {
		global $argv;
		echo <<<TEXT

Usage: {$argv[0]} <command> [<args>]

Type '{$argv[0]} commands' for a list of available commands.
Type '{$argv[0]} help <command>' for more info about a specific command


TEXT;
		exit;
	}

	/**
	 * Filter JSON must be readable and writable.
	 */
	public static function checkFilterJsonAccessibility() {
		$filter_json = Utilities::FILTER_JSON;
		if (file_exists($filter_json)) {
			if (!is_readable($filter_json) || !is_writable($filter_json)) {
				echo <<<TEXT

Cannot read or write {$filter_json}.


TEXT;
				exit;
			}
		} else {
			if (!is_writable(__DIR__)) {
				echo <<<TEXT

Cannot create {$filter_json}.


TEXT;
				exit;
			}
			file_put_contents($filter_json, "[]");
		}
	}
}

function main() {
	global $argc, $argv;
	if ($argc < 2) {
		Utilities::printUsageAndExit();
	}
	$command = CommandFactory::getCommand($argv[1]);
	if ($command) {
		$command->run();
	} else {
		Utilities::printUsageAndExit();
	}
}

main();
