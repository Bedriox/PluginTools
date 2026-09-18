<?php
declare(strict_types=1);
use PhpCsFixer\Config;
use PhpCsFixer\Finder;
return (new Config())->setRiskyAllowed(true)->setRules(['@PER-CS2x0'=>true,'@PHP8x4Migration'=>true,'declare_strict_types'=>true])->setFinder(Finder::create()->in([__DIR__.'/src',__DIR__.'/tests'])->name('*.php'));
