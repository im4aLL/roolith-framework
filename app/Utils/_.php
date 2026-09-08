<?php
namespace App\Utils;

/**
 * BC alias for App\Utils\Arr.
 *
 * The "_" class name is deprecated since PHP 8.4 (RFC: deprecate underscore
 * as standalone class name). New code should use App\Utils\Arr directly.
 * This file keeps the old name working via class_alias with a namespaced
 * alias (App\Utils\_), which does NOT trigger the PHP 8.4 deprecation
 * (only global "_" or direct `class _` does). The alias is created
 * lazily when this file is autoloaded for App\Utils\_.
 *
 * Autoload rationale: class_exists(Arr::class) allows Composer autoload to
 * load Arr via PSR-4 first, so this shim keeps working when the file moves
 * under a different PSR-4 root. The require_once fallback only runs when
 * autoload cannot provide Arr (for example a partial install). The alias
 * existence check uses the namespaced name with autoload disabled to avoid
 * re-entering this same autoloader and to prevent a duplicate class_alias
 * fatal on repeated inclusion.
 *
 * @deprecated Use App\Utils\Arr instead.
 */
if (!class_exists(Arr::class)) {
    require_once __DIR__ . '/Arr.php';
}

$alias = __NAMESPACE__ . '\_';

if (!class_exists($alias, false) && !interface_exists($alias, false) && !trait_exists($alias, false)) {
    class_alias(Arr::class, $alias);
}
