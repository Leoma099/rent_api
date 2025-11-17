<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePropertiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('landlord_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('address');
            $table->string('barangay');
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->decimal('price', 10, 2);
            $table->string('property_type')->nullable();
            $table->longText('photo_1')->nullable();
            $table->longText('photo_2')->nullable();
            $table->longText('photo_3')->nullable();
            $table->longText('photo_4')->nullable();
            $table->longText('floor_plan')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->tinyInteger('is_featured')->default(0);  // inactive by default
            $table->string('size');
            $table->tinyInteger('propertyStats')->default(0);
            $table->timeStamp('last_viewed_at')->nullable()->after('created_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('properties');
    }
}
