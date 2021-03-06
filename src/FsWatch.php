<?php

namespace Rx\React;

use React\EventLoop\LoopInterface;
use Rx\DisposableInterface;
use Rx\Observable;
use Rx\ObserverInterface;
use Rx\Subject\Subject;

class FsWatch extends Observable
{
    private $process;
    private $errors;

    public function __construct(string $path, string $options = null, LoopInterface $loop = null)
    {
        $cmd = "fswatch -xrn {$path} {$options}";

        $this->errors  = new Subject();
        $this->process = new ProcessSubject($cmd, $this->errors, null, null, [], $loop);
    }

    public function _subscribe(ObserverInterface $observer) : DisposableInterface
    {
        return $this->process
            ->merge($this->errors->map(function (\Throwable $ex) {
                throw $ex;
            }))
            ->flatMap(function ($data) {
                return Observable::fromArray(array_map(function (string $file) {
                    list($file, $bitwise) = explode(' ', $file);
                    return new WatchEvent($file, (int) $bitwise);
                }, explode("\n", trim($data))));
            })
            ->subscribe($observer);
    }

    public function getSubject() : ProcessSubject
    {
        return $this->process;
    }
}
