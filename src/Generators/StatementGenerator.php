<?php

namespace Blueprint\Generators;

use Blueprint\Blueprint;
use Blueprint\Contracts\Generator;
use Illuminate\Filesystem\Filesystem;

abstract class StatementGenerator implements Generator
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var string
     */
    protected $new_instance = 'new instance';

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    protected function buildConstructor($statement)
    {
        static $constructor = null;

        if (is_null($constructor)) {
            $constructor = str_replace('new instance', $this->new_instance, $this->filesystem->stub('constructor.stub'));
        }

        if (empty($statement->data())) {
            $stub = (str_replace('{{ body }}', '//', $constructor));
        } else {
            $stub = $this->buildProperties($statement->data()) . PHP_EOL . PHP_EOL;
            $stub .= str_replace('__construct()', '__construct(' . $this->buildParameters($statement->data()) . ')', $constructor);
            $stub = str_replace('{{ body }}', $this->buildAssignments($statement->data()), $stub);
        }

        return trim($stub);
    }

    protected function buildProperties(array $data)
    {
        return trim(
            array_reduce(
                $data,
                function ($output, $property) {
                    $output .= '    public $' . $property . ';' . PHP_EOL . PHP_EOL;

                    return $output;
                },
                ''
            )
        );
    }

    protected function buildAssignments(array $data)
    {
        return trim(
            array_reduce(
                $data,
                function ($output, $property) {
                    $output .= '        $this->' . $property . ' = $' . $property . ';' . PHP_EOL;

                    return $output;
                },
                ''
            )
        );
    }

    protected function buildParameters(array $data)
    {
        $parameters = array_map(
            function ($parameter) {
                return '$' . $parameter;
            },
            $data
        );

        return implode(', ', $parameters);
    }

    protected function buildTypehint(array $data)
    {
        $result = array_reduce(
            $data,
            function ($output, $property) {
                $output .= ' * @var mixed $' . $property . PHP_EOL;

                return $output;
            },
            ''
        );
        if ($result) {
            $result = '<' . '?php /**' . PHP_EOL . $result . ' */ ?'.'>';
        }

        return $result;
    }
}
