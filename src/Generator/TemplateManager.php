<?php

namespace Toramanlis\ImplicitMigrations\Generator;

class TemplateManager
{
    protected const TAB_SIZE = 4;

    protected string $template;

    public function __construct(string $templateName)
    {
        $templatePath = __DIR__ . '/../templates/' . $templateName;
        $this->template = file_get_contents(realpath($templatePath));
    }

    public function substitute(string $key, string $value): static
    {
        $placeholder = "<<{$key}>>";
        $placeholderPosition = strpos($this->template, $placeholder);
        $previousNewlinePosition = strrpos($this->template, "\n", $placeholderPosition - strlen($this->template));
        $afterNewline = substr($this->template, $previousNewlinePosition + 1);

        preg_match('/^\s+/', $afterNewline, $matches);
        $indentation = $matches[0];

        $value = strtr($value, [
            "\t" => str_repeat(' ', static::TAB_SIZE),
            "\n" => "\n{$indentation}",
        ]);

        /** @var string */
        $value = preg_replace('/\n\s+\n/', "\n\n", $value);

        $this->template = str_replace($placeholder, $value, $this->template);

        return $this;
    }

    /**
     * @param array<string, string> $data
     * @return string
     */
    public function process(array $data): string
    {
        foreach ($data as $key => $value) {
            $this->substitute($key, $value);
        }

        return $this->template;
    }
}
