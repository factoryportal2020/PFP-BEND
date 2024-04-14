<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateItem extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('admin_id');
            $table->bigInteger('domain_id');
            $table->string('name',100);
            $table->bigInteger('category_id');
            $table->string('specification',2000)->nullable();
            $table->string('price',100)->nullable();
            $table->string('note',100)->nullable();
            $table->string('description',2000)->nullable();
            $table->string('code',50);
            $table->tinyInteger('status')->default(1);
            $table->tinyInteger('is_show')->default(1);
            $table->bigInteger('created_by');
            $table->bigInteger('updated_by');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('item_images', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('item_id');
            $table->string('name',100);
            $table->string('path',255);
            $table->string('size',50)->nullable();
            $table->string('type',100)->nullable();
            $table->bigInteger('created_by');
            $table->bigInteger('updated_by');
            $table->timestamps();
        });

        Schema::create('item_specifications', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('item_id');
            $table->string('label_name', 100);
            $table->string('type', 20)->nullable();
            $table->string('value', 100);
        });

        Schema::create('item_breakdowns', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('item_id');
            $table->string('label_name', 100);
            $table->string('value', 100);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('item');
        Schema::dropIfExists('item_images');
        Schema::dropIfExists('item_specifications');
        Schema::dropIfExists('item_breakdowns');
    }
}
