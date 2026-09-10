<?php

namespace Dth\Email\Services;

class TemplateRenderer
{
    public function render(string $content, array $variables, bool $escape = true): string
    {
        return (string) preg_replace_callback(
            '/{{\s*([a-zA-Z0-9_.-]+)\s*}}/',
            function (array $matches) use ($variables, $escape): string {
                $value = data_get($variables, $matches[1], '');
                $value = is_scalar($value) || $value === null ? (string) $value : '';

                return $escape ? e($value) : $value;
            },
            $content,
        );
    }

    public function renderSubject(string $subject, array $variables): string
    {
        return trim(strip_tags($this->render($subject, $variables, false)));
    }

    public function renderPreheader(?string $preheader, array $variables): ?string
    {
        if ($preheader === null || trim($preheader) === '') {
            return null;
        }

        return trim(strip_tags($this->render($preheader, $variables, false)));
    }

    public function renderText(?string $text, array $variables): ?string
    {
        if ($text === null || trim($text) === '') {
            return null;
        }

        return $this->render($text, $variables, false);
    }

    /**
     * @return list<string>
     */
    public function extractVariables(?string ...$contents): array
    {
        $variables = [];

        foreach ($contents as $content) {
            if ($content === null || $content === '') {
                continue;
            }

            preg_match_all('/{{\s*([a-zA-Z0-9_.-]+)\s*}}/', $content, $matches);

            foreach ($matches[1] ?? [] as $variable) {
                $variables[(string) $variable] = true;
            }
        }

        $keys = array_keys($variables);
        sort($keys);

        return array_values($keys);
    }

    /**
     * @param list<string> $requiredVariables
     * @return list<string>
     */
    public function missingVariables(array $requiredVariables, array $variables): array
    {
        return array_values(array_filter(
            $requiredVariables,
            static fn (string $key): bool => ! data_has($variables, $key),
        ));
    }
}
