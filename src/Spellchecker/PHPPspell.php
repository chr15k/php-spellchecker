<?php

declare(strict_types=1);

namespace PhpSpellcheck\Spellchecker;

use PhpSpellcheck\Exception\RuntimeException;
use PhpSpellcheck\Misspelling;
use Webmozart\Assert\Assert;

class PHPPspell implements SpellcheckerInterface
{
    /**
     * @var int
     */
    private $mode;

    /**
     * @var int
     */
    private $numberOfCharactersLowerLimit;

    /**
     * @var Aspell
     */
    private $aspell;

    /**
     * @see http://php.net/manual/en/function.pspell-config-mode.php
     * @see http://php.net/manual/en/function.pspell-config-ignore.php
     *
     * @param int|null $mode the mode parameter is the mode in which the spellchecker will work
     * @param int $numberOfCharactersLowerLimit Words less than n characters will be skipped
     * @param Aspell|null $aspell Aspell spellchecker that pspell extension is using underneath. Used to help retrieve supported languages
     */
    public function __construct(
        ?int $mode = null,
        int $numberOfCharactersLowerLimit = 0,
        ?Aspell $aspell = null
    ) {
        if (!\extension_loaded('pspell')) {
            throw new RuntimeException('Pspell extension must be loaded to use the PHPPspell spellchecker');
        }

        if ($mode === null) {
            $mode = PSPELL_FAST;
        }

        Assert::greaterThanEq($numberOfCharactersLowerLimit, 0);

        $this->mode = $mode;
        $this->numberOfCharactersLowerLimit = $numberOfCharactersLowerLimit;
        $this->aspell = $aspell ?? Aspell::create();
    }

    /**
     * {@inheritdoc}
     */
    public function check(
        string $text,
        array $languages,
        array $context
    ): iterable {
        Assert::count($languages, 1, 'PHPPspell spellchecker doesn\'t support multi-language check');

        $chosenLanguage = current($languages);
        $pspellConfig = pspell_config_create($chosenLanguage);
        pspell_config_mode($pspellConfig, $this->mode);
        pspell_config_ignore($pspellConfig, $this->numberOfCharactersLowerLimit);
        $dictionary = \PhpSpellcheck\pspell_new_config($pspellConfig);

        $lines = explode(PHP_EOL, $text);

        /** @var string $line */
        foreach ($lines as $lineNumber => $line) {
            $words = explode(' ', \PhpSpellcheck\preg_replace("/(?!['’-])(\p{P}|\+|--)/u", '', $line));
            foreach ($words as $word) {
                if (!pspell_check($dictionary, $word)) {
                    $suggestions = pspell_suggest($dictionary, $word);
                    Assert::isArray(
                        $suggestions,
                        \sprintf('pspell_suggest method failed with language "%s" and word "%s"', $chosenLanguage, $word)
                    );

                    yield new Misspelling($word, 0, $lineNumber + 1, $suggestions, $context);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedLanguages(): iterable
    {
        return $this->aspell->getSupportedLanguages();
    }
}
