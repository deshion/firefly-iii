<?php

/**
 * 2017_06_02_105232_changes_for_v450.php
 * Copyright (c) 2019 james@firefly-iii.org.
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

use Doctrine\DBAL\Schema\Exception\ColumnDoesNotExist;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class ChangesForV450.
 *
 * @codeCoverageIgnore
 */
class ChangesForV450 extends Migration
{
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // split up for sqlite compatibility
        try {
            Schema::table(
                'transactions',
                static function (Blueprint $table) {
                    $table->dropColumn('foreign_amount');
                }
            );
        } catch (QueryException|ColumnDoesNotExist $e) {
            Log::error(sprintf('Could not execute query: %s', $e->getMessage()));
            Log::error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
        }

        try {
            Schema::table(
                'transactions',
                static function (Blueprint $table) {
                    // cannot drop foreign keys in SQLite:
                    if ('sqlite' !== config('database.default')) {
                        $table->dropForeign('transactions_foreign_currency_id_foreign');
                    }
                }
            );
        } catch (QueryException $e) {
            Log::error(sprintf('Could not execute query: %s', $e->getMessage()));
            Log::error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
        }

        try {
            Schema::table(
                'transactions',
                static function (Blueprint $table) {
                    $table->dropColumn('foreign_currency_id');
                }
            );
        } catch (QueryException|ColumnDoesNotExist $e) {
            Log::error(sprintf('Could not execute query: %s', $e->getMessage()));
            Log::error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
        }
    }

    /**
     * Run the migrations.
     *
     */
    public function up(): void
    {
        // add "foreign_amount" to transactions
        try {
            Schema::table(
                'transactions',
                static function (Blueprint $table) {
                    $table->decimal('foreign_amount', 32, 12)->nullable()->after('amount');
                }
            );
        } catch (QueryException $e) {
            Log::error(sprintf('Could not execute query: %s', $e->getMessage()));
            Log::error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
        }

        // add foreign transaction currency id to transactions (is nullable):
        try {
            Schema::table(
                'transactions',
                static function (Blueprint $table) {
                    $table->integer('foreign_currency_id', false, true)->default(null)->after('foreign_amount')->nullable();
                    $table->foreign('foreign_currency_id')->references('id')->on('transaction_currencies')->onDelete('set null');
                }
            );
        } catch (QueryException $e) {
            Log::error(sprintf('Could not execute query: %s', $e->getMessage()));
            Log::error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
        }
    }
}
