<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLandmarksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('landmarks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('property_id');
            $table->bigInteger('landlord_id');
            $table->text('name');            // Name of landmark
            $table->string('vicinity');        // Address or vicinity
            $table->decimal('distance', 8, 2); // e.g., 12345.67 km
            $table->decimal('lat', 10, 7);     // Latitude with 7 decimals
            $table->decimal('lng', 10, 7);
            $table->string('type'); 
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
        Schema::dropIfExists('landmarks');
    }
}
