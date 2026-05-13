<?php

spl_autoload_register(function ($class) {
    static $legacyMap = null;

    $baseDir = __DIR__ . '/';
    $classPath = str_replace('\\', '/', $class);

    $directFile = $baseDir . $classPath . '.php';
    if (is_file($directFile)) {
        require_once $directFile;
        return;
    }

    if (strpos($class, '\\') === false) {
        $legacyClass = $class;
        $legacyLower = strtolower($legacyClass);

        $legacyCandidates = array(
            $baseDir . 'classes/' . $legacyClass . '.php',
            $baseDir . 'classes/class.' . $legacyLower . '.php',
        );

        foreach ($legacyCandidates as $candidate) {
            if (is_file($candidate)) {
                require_once $candidate;
                if (class_exists($legacyClass, false) || interface_exists($legacyClass, false) || trait_exists($legacyClass, false)) {
                    return;
                }
            }
        }

        if ($legacyMap === null) {
            $legacyMap = array();
            foreach (glob($baseDir . 'classes/*.php') as $file) {
                $source = @file_get_contents($file);
                if ($source === false) {
                    continue;
                }

                if (!preg_match_all('/^\s*(?:final\s+|abstract\s+)?class\s+([A-Za-z_][A-Za-z0-9_]*)|^\s*interface\s+([A-Za-z_][A-Za-z0-9_]*)|^\s*trait\s+([A-Za-z_][A-Za-z0-9_]*)/m', $source, $matches, PREG_SET_ORDER)) {
                    continue;
                }

                $baseName = basename($file, '.php');
                $normalizedBase = strtolower(preg_replace('/[^a-z0-9]/i', '', preg_replace('/^class\./i', '', $baseName)));

                foreach ($matches as $match) {
                    $declaredClass = '';
                    for ($i = 1; $i <= 3; $i++) {
                        if (!empty($match[$i])) {
                            $declaredClass = $match[$i];
                            break;
                        }
                    }

                    if ($declaredClass === '') {
                        continue;
                    }

                    $normalizedClass = strtolower(preg_replace('/[^a-z0-9]/i', '', $declaredClass));
                    $score = 0;

                    if ($baseName === $declaredClass) {
                        $score = 300;
                    } elseif ($baseName === 'class.' . strtolower($declaredClass)) {
                        $score = 250;
                    } elseif ($normalizedBase === $normalizedClass) {
                        $score = 200;
                    } elseif (strpos($normalizedBase, $normalizedClass) !== false) {
                        $score = 100;
                    }

                    if (!isset($legacyMap[$declaredClass]) || $score > $legacyMap[$declaredClass]['score']) {
                        $legacyMap[$declaredClass] = array(
                            'file' => $file,
                            'score' => $score,
                        );
                    }
                }
            }
        }

        if (isset($legacyMap[$legacyClass]['file']) && is_file($legacyMap[$legacyClass]['file'])) {
            require_once $legacyMap[$legacyClass]['file'];
            if (class_exists($legacyClass, false) || interface_exists($legacyClass, false) || trait_exists($legacyClass, false)) {
                return;
            }
        }
    }

    throw new Exception("Класс [$class] не найден: $directFile");
});
