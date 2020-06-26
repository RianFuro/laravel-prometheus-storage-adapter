<?php namespace Tests\Constraints;

use PHPUnit\Framework\Constraint\Constraint;
use Prometheus\MetricFamilySamples;

class AdapterCollectedMetric extends Constraint
{
    private $name;
    private $labels;
    private $type;
    /**
     * @var string|null
     */
    private $help;

    public function __construct($type, $name, $labels, $help = null)
    {
        $this->name = $name;
        $this->labels = $labels;
        $this->type = $type;
        $this->help = $help;
    }

    /**
     * Returns a string representation of the constraint.
     */
    public function toString(): string
    {
        return 'contains metric';
    }

    /**
     * Evaluates the constraint for parameter $other. Returns true if the
     * constraint is met, false otherwise.
     *
     * @param mixed $other value or object to evaluate
     * @return bool
     */
    protected function matches($other): bool
    {
        return collect($other)->some(function (MetricFamilySamples $item) {
            return $item->getType() === $this->type
                && $item->getName() === $this->name
                && $item->getLabelNames() === $this->labels
                && (is_null($this->help) || $item->getHelp() === $this->help);
        });
    }

    /**
     * Returns the description of the failure
     *
     * The beginning of failure messages is "Failed asserting that" in most
     * cases. This method should return the second part of that sentence.
     *
     * @param mixed $other evaluated value or object
     * @return string
     */
    protected function failureDescription($other): string
    {
        $desc = sprintf("adapter collected a %s '%s' with labels %s",
            $this->type,
            $this->name,
            json_encode($this->labels));

        if (!is_null($this->help))
            $desc .= sprintf(" and help text '%s'", $this->help);

        return $desc;
    }
}