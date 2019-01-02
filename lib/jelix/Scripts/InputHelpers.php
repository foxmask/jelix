<?php
/**
 * @author      Laurent Jouanneau
 * @copyright   2018 Laurent Jouanneau
 * @link        http://www.jelix.org
 * @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
 */
namespace Jelix\Scripts;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Helper\QuestionHelper;

class InputHelpers {


    /**
     * @var QuestionHelper
     */
    protected $questionHelper = null;

    /**
     * @var InputInterface
     */
    protected $consoleInput = null;

    /**
     * @var OutputInterface
     */
    protected $consoleOutput = null;

    function __construct(QuestionHelper $helper, InputInterface $input, OutputInterface $output) {
        $this->questionHelper = $helper;
        $this->consoleInput = $input;
        $this->consoleOutput = $output;
    }


    /**
     * Ask a confirmation.
     *
     * @param string $questionMessage the question
     * @param bool $defaultResponse the default response
     * @return boolean true it the user has confirmed
     */
    public function askConfirmation($questionMessage, $defaultResponse = false) {
        $questionMessage = "<question>$questionMessage</question>";
        if (strpos($questionMessage, "\n") !== false) {
            $questionMessage.="\n";
        }
        $questionMessage .= " ( 'y' or 'n', default is ".($defaultResponse?'y':'n').')';
        $questionMessage .= "<inputstart> > </inputstart>";
        $question = new ConfirmationQuestion($questionMessage, $defaultResponse);
        return $this->questionHelper->ask($this->consoleInput, $this->consoleOutput, $question);
    }

    /**
     * Ask a value to the user.
     *
     * @param string $questionMessage
     * @param string|false $defaultResponse
     * @param string[]|false $autoCompleterValues list of values for autocompletion
     * @param callable|null $validator function to validate the value. It accepts
     *   a string as parameter, should return the value (may be modified), and
     *   should throw an exception when the value is invalid.
     * @return string the value given by the user
     */
    public function askInformation($questionMessage, $defaultResponse = false,
                                      $autoCompleterValues = false, $validator = null) {
        $questionMessage = "<question>$questionMessage</question>";
        if ($defaultResponse) {
            if (strpos($questionMessage, "\n") !== false) {
                $questionMessage.="\n";
            }
            $questionMessage .= " (default is '$defaultResponse')";
        }
        $questionMessage .= "<inputstart> > </inputstart>";
        $question = new Question($questionMessage, $defaultResponse);
        if (is_array($autoCompleterValues)) {
            $question->setAutocompleterValues($autoCompleterValues);
        }
        $question->setNormalizer(function ($value) {
            // $value can be null here
            return $value ? trim($value) : '';
        });

        if ($validator) {
            $question->setValidator($validator);
            $question->setMaxAttempts(10);
        }

        return $this->questionHelper->ask($this->consoleInput, $this->consoleOutput, $question);
    }

    /**
     * Ask a hidden value to the user, like a password
     *
     * @param string $questionMessage
     * @param string|false $defaultResponse
     * @return string the value
     */
    public function askSecretInformation($questionMessage, $defaultResponse = false) {
        $questionMessage = "<question>$questionMessage</question>";
        $questionMessage .= "<inputstart> > </inputstart>";
        $question = new Question($questionMessage, $defaultResponse);
        $question->setHidden(true);
        $question->setHiddenFallback(false);

        return $this->questionHelper->ask($this->consoleInput, $this->consoleOutput, $question);
    }

    /**
     * Ask a value from a choice
     *
     * @param string $questionMessage
     * @param array $choice list of possible values
     * @param int $defaultResponse the default value
     * @param bool $multipleChoice true if the user can choose different values
     * @param string $errorMessage error message when the user didn't indicate a value from the choice
     * @return string|string[]  responses from the user
     */
    public function askInChoice($questionMessage, array $choice,
                                   $defaultResponse=0, $multipleChoice = false,
                                   $errorMessage='%s is invalid') {
        $questionMessage = "<question>$questionMessage</question>";
        if (is_array($defaultResponse)) {
            $defaultResponse = implode(',', $defaultResponse);
        }
        if ($defaultResponse !== false) {
            if (strpos($questionMessage, "\n") !== false) {
                $questionMessage.="\n";
            }
            $questionMessage .= " (default is '$defaultResponse')";
        }
        $question = new ChoiceQuestion($questionMessage, $choice, $defaultResponse);
        $question->setErrorMessage($errorMessage);
        if ($multipleChoice) {
            $question->setMultiselect(true);
        }
        return $this->questionHelper->ask($this->consoleInput, $this->consoleOutput, $question);
    }


}
