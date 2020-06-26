<?php namespace LaravelPrometheusStorageAdapter;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Metric
 * @package RianFuro\LaravelPrometheusStorageAdapter
 *
 * @property int id
 * @property string type
 * @property string name
 * @property string help
 * @property string[] labels
 * @property string labels_hash
 * @property Collection samples
 */
class Metric extends Model
{
    protected $table = 'prometheus_metrics';
    public $timestamps = false;

    protected $casts = [
        'labels' => 'array'
    ];

    protected $guarded = [];

    public function samples()
    {
        return $this->hasMany(Sample::class);
    }
}
