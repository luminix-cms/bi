<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        Schema::create('bi_dashboards', function (Blueprint $table) {
            $table->ulid()->primary();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('options')->nullable();
            $table->timestamps();
        });

        Schema::create('bi_widgets', function (Blueprint $table) {
            $table->ulid()->primary();

            $table->foreignUlid('dashboard_ulid')
                ->constrained('bi_dashboards', 'ulid')
                ->cascadeOnDelete();

            $table->string('key');
            $table->string('name');
            $table->string('component')->nullable();
            $table->integer('width')->default(12);
            $table->json('extra')->nullable();
            $table->timestamps();
        });

        Schema::create('bi_dimensions', function (Blueprint $table) {
            $table->ulid()->primary();

            $table->foreignUlid('widget_ulid')
                ->constrained('bi_widgets', 'ulid')
                ->cascadeOnDelete();

            $table->string('key');
            $table->string('name');
            $table->string('type'); // DateDimension, GenericDimension, etc.
            $table->json('options')->nullable();
            $table->timestamps();
        });

        Schema::create('bi_metrics', function (Blueprint $table) {
            $table->ulid()->primary();

            $table->foreignUlid('widget_ulid')
                ->constrained('bi_widgets', 'ulid')
                ->cascadeOnDelete();

            $table->string('key');
            $table->string('name');
            $table->string('type'); // GenericMetric, etc.
            $table->json('options')->nullable();
            $table->timestamps();
        });

        Schema::create('bi_filters', function (Blueprint $table) {
            $table->ulid()->primary();

            $table->foreignUlid(('dashboard_ulid'))
                ->constrained('bi_dashboards', 'ulid')
                ->cascadeOnDelete();

            $table->string('key');
            $table->string('name');
            $table->string('type'); // GenericFilter, etc.
            $table->string('component')->nullable();
            $table->string('column')->nullable();
            $table->string('relation')->nullable();
            $table->json('options')->nullable();
            $table->timestamps();
        });

        Schema::create('bi_widgets_data', function (Blueprint $table) {
            $table->ulid()->primary();

            $table->foreignUlid(('widget_ulid'))
                ->constrained('bi_widgets', 'ulid')
                ->cascadeOnDelete();

            $table->json('data'); // Armazena os dados do widget
            $table->string('source_type'); // Tipo de fonte (jupyter, database, api, etc.)
            $table->json('source_details')->nullable(); // Detalhes da fonte de dados
            $table->timestamp('last_updated');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bi_filters');
        Schema::dropIfExists('bi_metrics');
        Schema::dropIfExists('bi_dimensions');
        Schema::dropIfExists('bi_widgets');
        Schema::dropIfExists('bi_dashboards');
        Schema::dropIfExists('bi_widgets_data');
    }
};
