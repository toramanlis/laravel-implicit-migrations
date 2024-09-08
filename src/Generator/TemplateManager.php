<?php

namespace Toramanlis\ImplicitMigrations\Generator;

class TemplateManager
{
    protected const TAB_SIZE = 4;

    protected string $template;

    public function __construct(string $templateName)
    {
        $templatePath = implode(DIRECTORY_SEPARATOR, [
            __DIR__,
            '..',
            'templates',
            $templateName
        ]);

        $this->template = file_get_contents(realpath($templatePath));
    }

    public static function substitute(string $subject, string $key, string $value): string
    {
        $placeholder = "<<{$key}>>";
        $placeholderPosition = strpos($subject, $placeholder);
        $previousNewlinePosition = strrpos($subject, "\n", $placeholderPosition - strlen($subject));
        $afterNewline = substr($subject, $previousNewlinePosition + 1);

        preg_match('/^\s+/', $afterNewline, $matches);
        $indentation = $matches[0];

        $value = strtr($value, [
            "\t" => str_repeat(' ', static::TAB_SIZE),
            "\n" => "\n{$indentation}",
        ]);

        /** @var string */
        $value = preg_replace('/\n\s+\n/', "\n\n", $value);

        return str_replace($placeholder, $value, $subject);
    }

    /**
     * @param array<string, string> $data
     * @return string
     */
    public function process(array $data): string
    {
        $subject = $this->template;

        foreach ($data as $key => $value) {
            $subject = static::substitute($subject, $key, $value);
        }

        return $subject;
    }
}
