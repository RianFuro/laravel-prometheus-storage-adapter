<?php namespace LaravelPrometheusStorageAdapter;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Sample
 * @package RianFuro\LaravelPrometheusStorageAdapter
 *
 * @property string[] labels
 * @property string labels_hash
 * @property int value
 * @property Collection histogram_buckets
 */
class Sample extends Model
{
    protected $table = 'prometheus_samples';
    public $timestamps = false;

    protected $casts = [
        'labels' => 'array'
    ];

    protected $guarded = [];

    function metric()
    {
        return $this->belongsTo(Metric::class);
    }

    public function histogram_buckets()
    {
        return $this->hasMany(HistogramBucket::class);
    }
}
