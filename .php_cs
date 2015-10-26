<?php

$gitIgnoreLines = array_map(function ($line) {
    return rtrim($line, "\r\n\\/");
}, file('.gitignore'));

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->in(__DIR__)
    ->exclude($gitIgnoreLines);

return \Symfony\CS\Config\Config::create()
    ->level(\Symfony\CS\FixerInterface::SYMFONY_LEVEL)
    ->fixers([
        '-psr0', // Does not play well with PSR-4
        'concat_with_spaces',
        '-concat_without_spaces',
        '-empty_return',
        '-phpdoc_no_empty_return',
        '-return',
        '-pre_increment',
        'header_comment',
    ])
    ->finder($finder);