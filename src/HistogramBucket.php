<?php namespace LaravelPrometheusStorageAdapter;

use Illuminate\Database\Eloquent\Model;

/**
 * Class HistogramBucket
 * @package RianFuro\LaravelPrometheusStorageAdapter
 *
 * @property string name
 * @property int value
 */
class HistogramBucket extends Model
{
    protected $table = 'prometheus_histogram_buckets';
    public $timestamps = false;

    protected $guarded = [];

    public function sample()
    {
        return $this->belongsTo(Sample::class);
    }
}
