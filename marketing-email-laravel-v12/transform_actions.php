<?php

$baseDir = 'E:/email and marketing-module/marketing-email-laravel-v12';

$files = [
    'app/Filament/Resources/CustomerDistributionBatchResource.php',
    'app/Filament/Resources/UserResource.php',
    'app/Filament/Resources/ContactQualificationResource.php',
    'app/Filament/Resources/PersonalContactResource.php',
    'app/Filament/Resources/ContactResource.php',
    'app/Filament/Resources/PositionResource.php',
    'app/Filament/Resources/MarketingCampaignResource.php',
    'app/Filament/Resources/LandingPageSubmissionResource.php',
    'app/Filament/Resources/CampaignResource.php',
    'app/Filament/Resources/StaffResource.php',
    'app/Filament/Resources/BusinessContactResource.php',
    'app/Filament/Resources/CustomerResource.php',
    'app/Filament/Resources/LandingPageResource.php',
    'app/Filament/Resources/FormTemplateResource.php',
    'app/Filament/Resources/EmailTemplateResource.php',
    'app/Filament/Resources/Sales/PaymentTrackingResource.php',
    'app/Filament/Resources/Sales/QuotationResource.php',
    'app/Filament/Resources/Sales/QuotationApprovalResource.php',
    'app/Filament/Resources/CustomerDistributionBatchResource/RelationManagers/ItemsRelationManager.php',
    'app/Filament/Resources/StaffResource/RelationManagers/WorkScheduleRelationManager.php',
    'app/Filament/Resources/StaffResource/RelationManagers/ScheduleRelationManager.php',
    'app/Filament/Resources/StaffResource/RelationManagers/InteractionsRelationManager.php',
    'app/Filament/Resources/StaffResource/RelationManagers/AvailabilitiesRelationManager.php',
    'app/Filament/Resources/CustomerResource/RelationManagers/InteractionsRelationManager.php',
    'app/Filament/Resources/CustomerResource/RelationManagers/AssignmentsRelationManager.php',
    'app/Filament/Resources/Sales/QuotationResource/RelationManagers/ItemsRelationManager.php',
    'app/Filament/Resources/Sales/PriceBookResource/RelationManagers/PriceBookAccessRuleRelationManager.php',
    'app/Filament/Resources/LandingPageResource/RelationManagers/UtmUrlsRelationManager.php',
];

$modifiedFiles = [];

// String-aware bracket finder for '[' ']'
function findMatchingBracket($code, $openBracketPos, $limit)
{
    $depth = 1;
    $pos = $openBracketPos + 1;
    $len = strlen($code);
    $inString = false;
    $quoteChar = '';

    while ($pos < $len && $pos < $limit && $depth > 0) {
        $char = $code[$pos];

        if ($inString) {
            if ($char === '\\') {
                $pos += 2;

                continue;
            }
            if ($char === $quoteChar) {
                $inString = false;
            }
            $pos++;

            continue;
        }

        if ($char === '"' || $char === "'") {
            $inString = true;
            $quoteChar = $char;
            $pos++;

            continue;
        }

        if ($char === '/' && $pos + 1 < $len) {
            if ($code[$pos + 1] === '/') {
                while ($pos < $len && $code[$pos] !== "\n") {
                    $pos++;
                }

                continue;
            } elseif ($code[$pos + 1] === '*') {
                $pos += 2;
                while ($pos + 1 < $len && ! ($code[$pos] === '*' && $code[$pos + 1] === '/')) {
                    $pos++;
                }
                $pos += 2;

                continue;
            }
        }

        if ($char === '[') {
            $depth++;
        }
        if ($char === ']') {
            $depth--;
        }

        $pos++;
    }

    return $depth === 0 ? $pos - 1 : false;
}

// String-aware brace finder for '{' '}'
function findMatchingBrace($code, $openBracePos, $limit)
{
    $depth = 1;
    $pos = $openBracePos + 1;
    $len = strlen($code);
    $inString = false;
    $quoteChar = '';

    while ($pos < $len && $pos < $limit && $depth > 0) {
        $char = $code[$pos];

        if ($inString) {
            if ($char === '\\') {
                $pos += 2;

                continue;
            }
            if ($char === $quoteChar) {
                $inString = false;
            }
            $pos++;

            continue;
        }

        if ($char === '"' || $char === "'") {
            $inString = true;
            $quoteChar = $char;
            $pos++;

            continue;
        }

        if ($char === '/' && $pos + 1 < $len) {
            if ($code[$pos + 1] === '/') {
                while ($pos < $len && $code[$pos] !== "\n") {
                    $pos++;
                }

                continue;
            } elseif ($code[$pos + 1] === '*') {
                $pos += 2;
                while ($pos + 1 < $len && ! ($code[$pos] === '*' && $code[$pos + 1] === '/')) {
                    $pos++;
                }
                $pos += 2;

                continue;
            }
        }

        if ($char === '{') {
            $depth++;
        }
        if ($char === '}') {
            $depth--;
        }

        $pos++;
    }

    return $depth === 0 ? $pos - 1 : false;
}

// Get brace depth at a position (string-aware)
function getBraceDepthAt($code, $start, $end)
{
    $depth = 0;
    $pos = $start;
    $len = strlen($code);
    $inString = false;
    $quoteChar = '';

    while ($pos < $end && $pos < $len) {
        $char = $code[$pos];

        if ($inString) {
            if ($char === '\\') {
                $pos += 2;

                continue;
            }
            if ($char === $quoteChar) {
                $inString = false;
            }
            $pos++;

            continue;
        }

        if ($char === '"' || $char === "'") {
            $inString = true;
            $quoteChar = $char;
            $pos++;

            continue;
        }

        if ($char === '/' && $pos + 1 < $len) {
            if ($code[$pos + 1] === '/') {
                while ($pos < $len && $code[$pos] !== "\n") {
                    $pos++;
                }

                continue;
            } elseif ($code[$pos + 1] === '*') {
                $pos += 2;
                while ($pos + 1 < $len && ! ($code[$pos] === '*' && $code[$pos + 1] === '/')) {
                    $pos++;
                }
                $pos += 2;

                continue;
            }
        }

        if ($char === '{') {
            $depth++;
        }
        if ($char === '}') {
            $depth--;
        }

        $pos++;
    }

    return $depth;
}

function findTableMethodBody($code)
{
    // Find 'function table'
    $pattern = '/function\s+table\s*\(/';
    if (! preg_match($pattern, $code, $matches, PREG_OFFSET_CAPTURE)) {
        return null;
    }

    $methodDeclPos = $matches[0][1];

    // Find opening brace of method body
    $len = strlen($code);
    $bracePos = strpos($code, '{', $methodDeclPos);
    if ($bracePos === false) {
        return null;
    }

    // Find matching close brace
    $closeBrace = findMatchingBrace($code, $bracePos, $len);
    if ($closeBrace === false) {
        return null;
    }

    return [$bracePos + 1, $closeBrace];
}

function transformFile($filepath)
{
    global $baseDir;

    $code = file_get_contents($filepath);
    if ($code === false) {
        echo "  ERROR: Cannot read file\n";

        return false;
    }

    $len = strlen($code);

    // Check if ActionGroup import already exists
    $hasImport = strpos($code, 'use Filament\\Tables\\Actions\\ActionGroup;') !== false;

    $methodRange = findTableMethodBody($code);
    if ($methodRange === null) {
        echo "  SKIP: No table() method found\n";

        return false;
    }

    [$methodStart, $methodEnd] = $methodRange;

    // Find ->actions([ within method body at depth 1 (not inside closures)
    // We search for '->actions(' literally
    $actionsBlocks = [];
    $searchPos = $methodStart;

    while ($searchPos < $methodEnd) {
        // Find next '->actions'
        $needle = '->actions';
        $found = strpos($code, $needle, $searchPos);
        if ($found === false || $found >= $methodEnd) {
            break;
        }

        $absPos = $found;

        // Check what follows 'actions' - must be '(' (not 'headerActions' or 'bulkActions')
        // Actually, we searched for '->actions' so we got it. But we need to verify
        // the next non-space char after 'actions' is '('.
        // But more importantly, we need to make sure it's not part of 'headerActions' or 'bulkActions'

        // The needle is '->actions', so the match starts at the '-' of '->'
        // But in '->headerActions', the text is '->header' + 'Actions'
        // strpos('->actions', '->headerActions') - the needle '->actions' would NOT be found
        // within '->headerActions' because after '->' comes 'h' not 'a'.
        // Wait, but what if we search for '->actions' and the actual text is '->headerActions'?
        // The string '->headerActions' does NOT contain the substring '->actions'!
        // Because after '->', the next chars are 'headerActions', and 'actions' doesn't start at position 2.
        // Actually... hmm. '->headerActions' - does it contain '->actions'? No! Because '->' is followed by 'h', not 'a'.
        // So we're fine. Our search for '->actions' won't match '->headerActions' or '->bulkActions'.

        // Wait, but I need to double check. In the code:
        // '->headerActions([' - the substring '->actions' does NOT appear because '->h' != '->a'
        // So we're safe.

        // But actually, there's another issue: '->actions' could match within '->actionsExtra' or similar
        // Let me check the char after 'actions'
        $afterNeedle = $absPos + strlen($needle);
        $charAfter = $code[$afterNeedle] ?? '';
        // Skip whitespace
        $skip = 0;
        while ($afterNeedle + $skip < $len && in_array($code[$afterNeedle + $skip], [' ', "\t", "\n", "\r"])) {
            $skip++;
        }
        $charAfter = $code[$afterNeedle + $skip] ?? '';

        if ($charAfter !== '(') {
            $searchPos = $absPos + 1;

            continue;
        }

        // Check brace depth - must be 1 (inside method body, not in closure)
        $depth = getBraceDepthAt($code, $methodStart, $absPos);
        if ($depth > 1) {
            // Inside a closure or nested block - skip
            $searchPos = $afterNeedle + $skip + 1;

            continue;
        }

        // Find the opening '[' after '->actions('
        $bracketPos = $afterNeedle + $skip + 1; // position after '('
        while ($bracketPos < $len && in_array($code[$bracketPos], [' ', "\t", "\n", "\r"])) {
            $bracketPos++;
        }

        if ($code[$bracketPos] !== '[') {
            $searchPos = $bracketPos + 1;

            continue;
        }

        // Find matching ']'
        $bracketEnd = findMatchingBracket($code, $bracketPos, $methodEnd);
        if ($bracketEnd === false) {
            $searchPos = $bracketPos + 1;

            continue;
        }

        // Find closing ')'
        $parenEnd = $bracketEnd + 1;
        while ($parenEnd < $len && in_array($code[$parenEnd], [' ', "\t", "\n", "\r"])) {
            $parenEnd++;
        }

        if ($code[$parenEnd] !== ')') {
            $searchPos = $parenEnd + 1;

            continue;
        }

        $callEnd = $parenEnd + 1;

        // Check if array is empty
        $arrayContent = substr($code, $bracketPos + 1, $bracketEnd - $bracketPos - 1);
        if (trim($arrayContent) === '') {
            $searchPos = $callEnd;

            continue;
        }

        $actionsBlocks[] = [
            'callStart' => $absPos,
            'callEnd' => $callEnd,
            'arrayContent' => $arrayContent,
        ];

        $searchPos = $callEnd;
    }

    if (empty($actionsBlocks)) {
        echo "  SKIP: No transformable ->actions([) found in table()\n";

        return false;
    }

    echo '  Found '.count($actionsBlocks)." table row action block(s) to transform\n";

    // Apply transformations in reverse order (preserve positions)
    foreach (array_reverse($actionsBlocks) as $block) {
        $arrayContent = $block['arrayContent'];
        $replacement = '->actions([ActionGroup::make()->icon(\'heroicon-o-ellipsis-vertical\')->label(__(\'action.actions\'))->button()->actions(['.$arrayContent.'])])';

        $code = substr($code, 0, $block['callStart']).$replacement.substr($code, $block['callEnd']);
    }

    // Add import if needed
    if (! $hasImport) {
        $importInsertPos = null;

        // Look for existing Filament\Tables use statements and insert after the last one
        $offset = 0;
        while (preg_match('/^use\s+Filament\\\\Tables\\\\[^;]+;/m', $code, $matches, PREG_OFFSET_CAPTURE, $offset)) {
            $importInsertPos = $matches[0][1] + strlen($matches[0][0]);
            // Move to end of this line
            $lineEnd = strpos($code, "\n", $importInsertPos);
            if ($lineEnd !== false) {
                $importInsertPos = $lineEnd + 1;
            }
            $offset = $importInsertPos;
        }

        if ($importInsertPos === null) {
            // Look for any Filament use statement
            $offset = 0;
            while (preg_match('/^use\s+Filament\\\\[^;]+;/m', $code, $matches, PREG_OFFSET_CAPTURE, $offset)) {
                $importInsertPos = $matches[0][1] + strlen($matches[0][0]);
                $lineEnd = strpos($code, "\n", $importInsertPos);
                if ($lineEnd !== false) {
                    $importInsertPos = $lineEnd + 1;
                }
                $offset = $importInsertPos;
            }
        }

        if ($importInsertPos === null) {
            // Insert after namespace line
            if (preg_match('/^namespace\s+[^;]+;/m', $code, $matches, PREG_OFFSET_CAPTURE)) {
                $nsEnd = $matches[0][1] + strlen($matches[0][0]);
                $lineEnd = strpos($code, "\n", $nsEnd);
                if ($lineEnd !== false) {
                    $importInsertPos = $lineEnd + 1;
                }
            }
        }

        if ($importInsertPos !== null) {
            $code = substr($code, 0, $importInsertPos)."use Filament\\Tables\\Actions\\ActionGroup;\n".substr($code, $importInsertPos);
            echo "  Added ActionGroup import\n";
        }
    }

    file_put_contents($filepath, $code);

    return true;
}

foreach ($files as $file) {
    $filepath = $baseDir.'/'.$file;
    if (! file_exists($filepath)) {
        echo "\nSKIP (file not found): $file\n";

        continue;
    }
    $relativeFile = str_replace($baseDir.'/', '', $filepath);
    echo "\nProcessing: $relativeFile\n";
    $result = transformFile($filepath);
    if ($result) {
        $modifiedFiles[] = $relativeFile;
    }
}

echo "\n\n=== SUMMARY ===\n";
echo "Modified files:\n";
foreach ($modifiedFiles as $f) {
    echo "  - $f\n";
}
echo "\nTotal modified: ".count($modifiedFiles)."\n";

// Write modified files list
$summaryPath = $baseDir.'/modified_files.txt';
file_put_contents($summaryPath, implode("\n", $modifiedFiles));
echo "\nModified files list written to: modified_files.txt\n";
