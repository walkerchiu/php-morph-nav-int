<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class CreateWkMorphNavTable extends Migration
{
    public function up()
    {
        Schema::create(config('wk-core.table.morph-nav.navs'), function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->nullableMorphs('host');
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->string('type')->nullable();
            $table->string('serial')->nullable();
            $table->string('identifier');
            $table->string('url')->nullable();
            $table->string('target')->default('_self');
            $table->text('icon')->nullable();
            $table->unsignedBigInteger('order')->nullable();
            $table->boolean('is_enabled')->default(0);

            $table->timestampsTz();
            $table->softDeletes();

            $table->index('type');
            $table->index('serial');
            $table->index('identifier');
            $table->index('is_enabled');
            $table->index(['host_type', 'host_id', 'type']);
            $table->index(['host_type', 'host_id', 'type', 'is_enabled']);
        });
        if (!config('wk-morph-nav.onoff.core-lang_core')) {
            Schema::create(config('wk-core.table.morph-nav.navs_lang'), function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->morphs('morph');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('code');
                $table->string('key');
                $table->longText('value')->nullable();
                $table->boolean('is_current')->default(1);

                $table->timestampsTz();
                $table->softDeletes();

                $table->foreign('user_id')->references('id')
                    ->on(config('wk-core.table.user'))
                    ->onDelete('set null')
                    ->onUpdate('cascade');
            });
        }
    }

    public function down() {
        Schema::dropIfExists(config('wk-core.table.morph-nav.navs_lang'));
        Schema::dropIfExists(config('wk-core.table.morph-nav.navs'));
    }
}
