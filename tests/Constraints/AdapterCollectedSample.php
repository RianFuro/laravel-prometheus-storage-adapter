<?php namespace Tests\Constraints;

use PHPUnit\Framework\Constraint\Constraint;
use Prometheus\MetricFamilySamples;
use Prometheus\Sample;

class AdapterCollectedSample extends Constraint
{
    private $name;
    private $labels;
    private $sample_labels;
    private $value;
    private $type;
    private $sample_name;

    public function __construct(...$args)
    {
        if (count($args) == 4)
            list($type, $name, $labels, $value) = $args;
        else if (count($args) == 5)
            list($type, $name, $sample_name, $labels, $value) = $args;
        else
            list($type, $name, $sample_name, $labels, $sample_labels, $value) = $args;

        $this->name = $name;
        $this->labels = $labels;
        $this->value = $value;
        $this->type = $type;
        $this->sample_name = $sample_name ?? $name;
        $this->sample_labels = $sample_labels ?? [];
    }

    /**
     * @inheritDoc
     */
    public function toString(): string
    {
        return 'contains sample';
    }

    protected function matches($other): bool
    {
        return collect($other)->some(function (MetricFamilySamples $metric) {
            return $metric->getType() === $this->type
                && $metric->getName() === $this->name
                && $metric->getLabelNames() === array_keys($this->labels)
                && collect($metric->getSamples())->some(function (Sample $sample) {
                    return $sample->getName() === $this->sample_name
                        && $sample->getLabelNames() === array_keys($this->sample_labels)
                        && $sample->getLabelValues() === array_merge(
                            array_values($this->labels),
                            array_values($this->sample_labels))
                        && $sample->getValue() == $this->value;
                });
        });
    }

    protected function failureDescription($other): string
    {
        return sprintf("adapter collected a %s sample '%s' on '%s' with labels %s and value %d",
            $this->type,
            $this->sample_name,
            $this->name,
            json_encode(array_merge($this->labels, $this->sample_labels)),
            $this->value);
    }
}