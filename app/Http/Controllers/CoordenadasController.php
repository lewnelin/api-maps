<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class CoordenadasController extends Controller
{
    public function processarCSV()
    {
        set_time_limit(0);

        $csvFilePath = storage_path('app/locais.csv');
        $tempCsvFilePath = storage_path('app/locais_new.csv');
        $apiKey = env('GOOGLE_MAPS_API_KEY');

        if (!File::exists($csvFilePath)) {
            return response()->json(['error' => 'O arquivo CSV não foi encontrado'], 404);
        }

        $csvFile = fopen($csvFilePath, 'r');
        $tempCsvFile = fopen($tempCsvFilePath, 'w');

        // Lê a linha de cabeçalho
        $header = fgetcsv($csvFile);
        $header[] = 'latitude';
        $header[] = 'longitude';
        fputcsv($tempCsvFile, $header);

        while (($data = fgetcsv($csvFile, 1000, ',')) !== FALSE) {
            $title = $data[1]; // Assume que o título está na segunda coluna do CSV
            list($lat, $lng) = $this->getCoordinates($title, $apiKey);

            // Adiciona as coordenadas ao array de dados
            $data[] = $lat;
            $data[] = $lng;

            // Escreve a linha no arquivo CSV temporário
            fputcsv($tempCsvFile, [$title, $lat, $lng]);
        }

        fclose($csvFile);
        fclose($tempCsvFile);

        // Substitui o arquivo CSV original pelo arquivo temporário
        File::move($tempCsvFilePath, $csvFilePath);

        return response()->json(['message' => 'Processamento completo. O arquivo CSV foi atualizado com as coordenadas.']);
    }

    private function getCoordinates($address, $apiKey)
    {
        $response = Http::withOptions([
            'verify' => false, // Desabilita a verificação SSL
        ])->get('https://maps.googleapis.com/maps/api/geocode/json', [
                    'address' => $address,
                    'key' => $apiKey,
                ]);

        $data = $response->json();

        if ($data['status'] == 'OK') {
            $latitude = $data['results'][0]['geometry']['location']['lat'];
            $longitude = $data['results'][0]['geometry']['location']['lng'];
            return [$latitude, $longitude];
        } else {
            return [null, null];
        }
    }

}
