<?php

declare(strict_types=1);

namespace App\Models;

use App\Database\Db;
use App\Utils\HttpResponse;
use App\Utils\ValidationSchema;

trait ProductTrait
{
  public function save(): void
  {
    $data = $this->getData();
    $dbTable = $this->type;
    $validationSchema = new ValidationSchema($dbTable);
    $isValid = $validationSchema->validate($data);

    if (gettype($isValid) === 'string') {
      HttpResponse::invalidData($isValid);
      return;
    }

    $dbConn = Db::getConnection();

    $sqlValueString = join(', ', array_map(fn ($item) => ":" . $item, array_keys($data)));
    $sql = "INSERT INTO $dbTable VALUES ($sqlValueString)";
    $stmt = $dbConn->prepare($sql);
    try {
      $stmt->execute($data);
      HttpResponse::added();
    } catch (\Exception $e) {
      HttpResponse::dbError($e->getMessage());
    }
  }

  public static function findAll(string $dbTable): array
  {
    $dbConn = Db::getConnection();

    $sql = "SELECT * FROM $dbTable";
    $stmt = $dbConn->query($sql);
    $products = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    return array_map(fn ($p) => $p += ['type' => $dbTable], $products);
  }

  public static function trowDbError(\Exception $e)
  {
    HttpResponse::dbError($e->getMessage());
  }
}