<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class DataManager
{
    protected $data = [];

    public function __construct()
    {
        $this->loadData();
    }

    protected function loadData()
    {
        Log::info('DataManager: Iniciando carga de datos.');
        try {
            // Cargar información de carreras
            $carrerasPath = storage_path('app/data/carreras');
            if (File::exists($carrerasPath)) {
                Log::info('DataManager: Directorio de carreras encontrado: ' . $carrerasPath);
                $files = File::files($carrerasPath);
                if (empty($files)) {
                    Log::warning('DataManager: Directorio de carreras está vacío.');
                }
                foreach ($files as $file) {
                    $careerName = pathinfo($file->getFilename(), PATHINFO_FILENAME);
                    $this->data['carreras'][strtolower($careerName)] = json_decode(File::get($file->getPathname()), true);
                    Log::info("DataManager: Cargada información para la carrera: {$careerName}.");
                }
            } else {
                Log::error('DataManager: Directorio de carreras NO encontrado: ' . $carrerasPath);
            }

            // Cargar FAQs
            $faqsPath = storage_path('app/data/faqs.json');
            if (File::exists($faqsPath)) {
                $this->data['faqs'] = json_decode(File::get($faqsPath), true);
                Log::info("DataManager: Cargadas FAQs generales desde: {$faqsPath}.");
            } else {
                Log::error('DataManager: Archivo de FAQs NO encontrado: ' . $faqsPath);
            }

            // Cargar información general de UNEFA
            $unefaInfoPath = storage_path('app/data/unefa_info.json');
            if (File::exists($unefaInfoPath)) {
                $this->data['unefa_info'] = json_decode(File::get($unefaInfoPath), true);
                Log::info("DataManager: Cargada información general de la UNEFA desde: {$unefaInfoPath}.");
            } else {
                Log::error('DataManager: Archivo de información de UNEFA NO encontrado: ' . $unefaInfoPath);
            }

            // Cargar training_data (si lo usas para few-shot learning)
            $trainingDataPath = storage_path('app/data/training_data.json');
            if (File::exists($trainingDataPath)) {
                $this->data['training_data'] = json_decode(File::get($trainingDataPath), true);
                Log::info("DataManager: Cargada información de entrenamiento desde: {$trainingDataPath}.");
            } else {
                Log::warning('DataManager: Archivo de entrenamiento (training_data.json) NO encontrado: ' . $trainingDataPath . '. Si no lo usas, puedes ignorar esta advertencia.');
            }

            Log::info('DataManager: Carga de datos finalizada.');

        } catch (\Exception $e) {
            Log::error('DataManager: Error al cargar datos: ' . $e->getMessage());
        }
    }

    public function getCarreraInfo(string $careerName): ?array
    {
        return $this->data['carreras'][strtolower($careerName)] ?? null;
    }

    public function getFaqAnswer(string $question): ?string
    {
        foreach (($this->data['faqs'] ?? []) as $faq) {
            // Asegurarse de que $faq es un array y que las claves 'pregunta' y 'respuesta' existen
            if (is_array($faq) && isset($faq['pregunta']) && isset($faq['respuesta'])) {
                if (stripos($faq['pregunta'], $question) !== false) {
                    return $faq['respuesta'];
                }
            } else {
                // Opcional: Loggear si una entrada de FAQ tiene un formato inesperado
                Log::warning('DataManager: Entrada de FAQ con formato inesperado encontrada: ' . json_encode($faq));
            }
        }
        return null;
    }

    public function getUnefaInfo(string $key): ?string
    {
        return $this->data['unefa_info'][$key] ?? null;
    }

    public function getAllCarrerasNames(): array
    {
        return array_keys($this->data['carreras'] ?? []);
    }

    public function getTrainingData(): array
    {
        return $this->data['training_data'] ?? [];
    }
}
