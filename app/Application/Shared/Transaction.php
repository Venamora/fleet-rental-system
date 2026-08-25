<?php
namespace App\Application\Shared;

interface Transaction { public function run(\Closure $operation): mixed; }
