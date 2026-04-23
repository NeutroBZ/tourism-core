<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tourism_Core_Loader {
	/** @var array<int, array{hook:string,component:object,callback:string,priority:int,accepted_args:int}> */
	private $actions = array();

	/** @var array<int, array{hook:string,component:object,callback:string,priority:int,accepted_args:int}> */
	private $filters = array();

	public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->actions[] = array(
			'hook'          => (string) $hook,
			'component'     => $component,
			'callback'      => (string) $callback,
			'priority'      => (int) $priority,
			'accepted_args' => (int) $accepted_args,
		);
	}

	public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->filters[] = array(
			'hook'          => (string) $hook,
			'component'     => $component,
			'callback'      => (string) $callback,
			'priority'      => (int) $priority,
			'accepted_args' => (int) $accepted_args,
		);
	}

	public function run() {
		foreach ( $this->filters as $hook ) {
			add_filter(
				$hook['hook'],
				array( $hook['component'], $hook['callback'] ),
				$hook['priority'],
				$hook['accepted_args']
			);
		}

		foreach ( $this->actions as $hook ) {
			add_action(
				$hook['hook'],
				array( $hook['component'], $hook['callback'] ),
				$hook['priority'],
				$hook['accepted_args']
			);
		}
	}
}

