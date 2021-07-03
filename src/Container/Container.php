<?php

namespace Bitrock\Container;
use \DI\ContainerBuilder;
use Bitrock\Models\Singleton;

class Container
{
    protected $builder;
    protected $config = [];

    public function __construct()
    {
        $this->builder = new ContainerBuilder();
    }

    /** Метод достает параметры из метода и аргументы, и превращает их в единый массив
     * @param array $methodParams
     * @param array $params
     * @return array
     */
    public function resolveArguments(array $methodParams, array $params): array
    {
        if (empty($methodParams) && empty($params)) return [];
        $containerParams = [];
        foreach ($methodParams as $methodParam) {
            $paramType = $methodParam->getType();
            if (
                !empty($paramType)
                && $paramType->getName() !== null
            ) {
                try {
                    $paramNamespace = $methodParam->getType()->getName();
                    if ($this->checkSingletonByClassName($paramNamespace)) {
                        $containerParams[] = $paramNamespace::getInstance();
                    } else {
                        $containerParams[] = new $paramNamespace();
                    }
                } catch (\ReflectionException $e) {
                    die($e->getMessage());
                }
            }
        }

        if (count($params)) {
            $containerParams = array_merge($containerParams, $params);
        }

        return $containerParams;
    }

    public function handle($controllerArray, $args)
    {
        if (empty($controllerArray[0] || empty($controllerArray[1]))) return false;

        $class = $controllerArray[0];
        $method = $controllerArray[1];
        $this->builder->addDefinitions($this->getConfig());
        $instance = $this->builder->build();

        try {
            $reflectionClass = new \ReflectionClass($class);
            $currentMethod = $reflectionClass->getMethod($method);
            $currentMethodParams = $currentMethod->getParameters();
            $params = $this->resolveArguments($currentMethodParams, $args);
            return $instance->call([$class, $method], $params);
        } catch(\ReflectionException $e) {
            die($e->getMessage());
        }
    }

    private function checkSingletonByClassName($className)
    {
        if (empty($className)) return false;

        $reflection = new \ReflectionClass($className);
        return $reflection->isSubclassOf(Singleton::class);
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function setConfig($configArray = [])
    {
        if (empty($configArray)) return false;

        $this->config = array_merge($this->config, $configArray);
        return true;
    }
}