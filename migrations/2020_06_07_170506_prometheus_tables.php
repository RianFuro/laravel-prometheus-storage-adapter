<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PrometheusTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prometheus_metrics', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('type');
            $table->json('labels');
            $table->string('labels_hash');
            $table->string('help');
        });

        Schema::create('prometheus_samples', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->json('labels');
            $table->string('labels_hash');
            $table->float('value');
            $table->unsignedBigInteger('metric_id');

            $table->foreign('metric_id')->references('id')->on('prometheus_metrics');
        });

        Schema::create('prometheus_histogram_buckets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->float('value');
            $table->unsignedBigInteger('sample_id');

            $table->foreign('sample_id')->references('id')->on('prometheus_samples');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('prometheus_histogram_buckets');
        Schema::dropIfExists('prometheus_samples');
        Schema::dropIfExists('prometheus_metrics');
    }
}
