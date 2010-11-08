<?php

	require_once "lib/spyc/spyc.php";

	class PluginManagerImpl {
	
		public function __construct($pluginsDir) {
			$this->pluginsDir = $pluginsDir;
			$this->loadedPlugins = array();
			$this->services = array();
			$this->actionProcessors = array();
		}
		
		private function loadPlugin($pluginDir) {
			if(in_array($pluginDir, $this->loadedPlugins))
				return;
			$plugin_conf = spyc_load_file($pluginDir . "/plugin.yaml");
			$plugin_class = $plugin_conf['plugin_class'];
			$deps = $plugin_conf['dependencies'];
			if($deps)
				$this->loadPluginDependencies($deps);
			include($pluginDir . '/' . $plugin_class . ".class.php");
			$plugin = new $plugin_class;
			$plugin->init($this);
			$this->loadedPlugins[] = $pluginDir;
		}
		
		private function loadPluginDependencies($deps) {
			$deps = split(',', $deps);
			foreach($deps as $dep)
				$this->loadPlugin($this->pluginsDir . '/' . $dep);
		}
		
		public function init() {
			$dh = opendir($this->pluginsDir);
			while ($pluginDir = readdir($dh)) {
				if(strpos($pluginDir, '.') === 0)
					continue;
				$this->loadPlugin($this->pluginsDir . '/' . $pluginDir);
			}
			closedir($dh);		
		}
		
		public function registerService($name, $service) {
			$this->services[$name] = $service;
		}
		
		public function getService($name) {
			return $this->services[$name];
		}
		
		public function addActionProcessor($actionName, $processor) {
			$actionProcessors = &$this->actionProcessors;
			if(!isset($actionProcessors[$actionName]))
				$actionProcessors[$actionName] = array();
			$actionProcessors[$actionName][] = $processor;	
		}
		
		public function callAction($actionName, &$context) {
			$actionProcessors = &$this->actionProcessors;
			$actionList = $actionProcessors[$actionName];
			foreach($actionList as $action)
				$action->call($context);
		}
	}
	
?>