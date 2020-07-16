<?php namespace LaravelPrometheusStorageAdapter;

use Prometheus\MetricFamilySamples;
use Prometheus\Storage\Adapter;

class EloquentStorageAdapter implements Adapter
{
    public function collect()
    {
        $metrics = Metric::with(['samples.histogram_buckets'])->get();
        return $metrics->toBase()->map(function (Metric $metric) {
            if ($metric->type == 'histogram') {
                return new MetricFamilySamples([
                    'name' => $metric->name,
                    'type' => $metric->type,
                    'help' => $metric->help,
                    'labelNames' => $metric->labels,
                    'buckets' => $metric->samples->first()->histogram_buckets->pluck('name'),
                    'samples' => $metric->samples->toBase()->map(function (Sample $sample) use ($metric) {
                        $acc = 0;
                        return $sample->histogram_buckets->toBase()->map(function (HistogramBucket $bucket) use ($metric, $sample, &$acc) {
                            $acc += $bucket->value;
                            return [
                                'name' => $metric->name . '_bucket',
                                'labelNames' => ['le'],
                                'labelValues' => array_merge($sample->labels, [$bucket->name]),
                                'value' => $acc
                            ];
                        })->merge([
                            [
                                'name' => $metric->name . '_count',
                                'labelNames' => [],
                                'labelValues' => $sample->labels,
                                'value' => $sample->histogram_buckets->pluck('value')->sum()
                            ],
                            [
                                'name' => $metric->name . '_sum',
                                'labelNames' => [],
                                'labelValues' => $sample->labels,
                                'value' => $sample->value
                            ]
                        ])->toArray();
                    })->flatten(1)->toArray()
                ]);
            } else {
                return new MetricFamilySamples([
                    'name' => $metric->name,
                    'type' => $metric->type,
                    'help' => $metric->help,
                    'labelNames' => $metric->labels,
                    'samples' => $metric->samples->toBase()->map(function (Sample $sample) use ($metric) {
                        return [
                            'name' => $metric->name,
                            'labelNames' => [],
                            'labelValues' => $sample->labels,
                            'value' => $sample->value
                        ];
                    })
                ]);
            }
        })->toArray();
    }

    /**
     * @param array $data {
     *  string name
     *  string help
     *  string[] labelNames
     *  string[] labelValues
     *  int[] buckets
     *  int command
     *  int|float value
     * }
     */
    public function updateHistogram(array $data): void
    {
        $metric = Metric::firstOrCreate([
            'type' => 'histogram',
            'name' => $data['name'],
            'labels_hash' => md5(implode(':', $data['labelNames']))
        ], [
            'help' => $data['help'],
            'labels' => $data['labelNames'],
        ]);

        /** @var Sample $sample */
        $sample = $metric->samples()->firstOrNew([
            'labels_hash' => md5(implode(':', $data['labelValues']))
        ], [
            'labels' => $data['labelValues'],
            'value' => 0,
        ]);
        if (!$sample->exists) {
            $sample->save();
            $sample->histogram_buckets()->saveMany(
                array_map(function ($bucket) {
                    return new HistogramBucket([
                        'name' => $bucket,
                        'value' => 0
                    ]);
                }, array_merge($data['buckets'], ['+Inf']))
            );
        }
        $sample->update([
            'value' => $sample->value + $data['value']
        ]);

        $bucketToIncrease = '+Inf';
        foreach ($data['buckets'] as $bucket) {
            if ($data['value'] <= $bucket) {
                $bucketToIncrease = $bucket;
                break;
            }
        }

        /** @var HistogramBucket $bucket */
        $bucket = $sample->histogram_buckets()->firstOrCreate([
            'name' => $bucketToIncrease
        ], [
            'value' => 0
        ]);
        $bucket->update([
            'value' => $bucket->value + 1
        ]);
    }

    /**
     * @param array $data {
     *  string name
     *  string help
     *  string[] labelNames
     *  string[] labelValues
     *  int command
     *  int|float value
     * }
     */
    public function updateGauge(array $data): void
    {
        $metric = Metric::firstOrCreate([
            'type' => 'gauge',
            'name' => $data['name'],
            'labels_hash' => md5(implode(':', $data['labelNames']))
        ], [
            'help' => $data['help'],
            'labels' => $data['labelNames']
        ]);

        /** @var Sample $sample */
        $sample = $metric->samples()->firstOrCreate([
            'labels_hash' => md5(implode(':', $data['labelValues']))
        ], [
            'labels' => $data['labelValues'],
            'value' => 0
        ]);

        switch ($data['command'])
        {
            case Adapter::COMMAND_SET:
                $sample->update(['value' => $data['value']]);
                break;
            default:
                $sample->update([
                    'value' => $sample->value + $data['value']
                ]);
                break;
        }
    }

    /**
     * @param array $data {
     *  string name
     *  string help
     *  string[] labelNames
     *  string[] labelValues
     *  int command
     * }
     */
    public function updateCounter(array $data): void
    {
        $metric = Metric::firstOrCreate([
            'type' => 'counter',
            'name' => $data['name'],
            'labels_hash' => md5(implode(':', $data['labelNames']))
        ], [
            'help' => $data['help'],
            'labels' => $data['labelNames']
        ]);

        /** @var Sample $sample */
        $sample = $metric->samples()->firstOrCreate([
            'labels_hash' => md5(implode(':', $data['labelValues']))
        ], [
            'labels' => $data['labelValues'],
            'value' => 0
        ]);

        switch ($data['command'])
        {
            case Adapter::COMMAND_SET:
                $sample->update(['value' => 0]);
                break;
            default:
                $sample->update([
                    'value' => $sample->value + 1
                ]);
                break;
        }
    }
}