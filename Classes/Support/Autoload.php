<?php

namespace CRMConnector\CRMSingleSignOn\Support;

class Autoload
{
    private $pluginPath;
    private $baseNamespace;

    public function __construct()
    {
        $this->pluginPath = dirname(dirname(dirname(__FILE__)));
        spl_autoload_register([$this, 'loader'], true, true);
    }

    public function loader($className)
    {
        if ($filename = $this->convertClassNameToPath($className)) {
            require_once $filename;
        }
    }

    private function convertClassNameToPath($className)
    {
        $baseNamespace = $this->getBaseNamespace();
        if (strpos($className, $baseNamespace."\\")===0) {
            $path = $this->pluginPath.'/'.str_replace("\\", "/", "Classes".substr($className, strlen($baseNamespace))).'.php';
            if (file_exists($path)) {
                return $path;
            }
        }
        return false;
    }

    private function getBaseNamespace()
    {
        if (is_null($this->baseNamespace)) {
            $namespace = explode("\\", __NAMESPACE__);
            $this->baseNamespace = $namespace[0]."\\".$namespace[1];
        }
        return $this->baseNamespace;
    }
}

$autoloader = new Autoload();