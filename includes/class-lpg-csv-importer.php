<?php

defined('ABSPATH') || exit;

class LPG_CSV_Importer
{
    const MAX_FILE_SIZE = 2097152;
    const MAX_ROWS      = 500;

    /**
     * Lit un fichier CSV temporaire et retourne ses données normalisées.
     *
     * @param string $file_path Chemin du fichier temporaire.
     *
     * @return array
     */
    public function import($file_path)
    {
        $result = [
            'headers'   => [],
            'rows'      => [],
            'delimiter' => '',
            'errors'    => [],
        ];

        if (!is_string($file_path) || '' === $file_path || !is_file($file_path)) {
            $result['errors'][] = 'Le fichier CSV temporaire est introuvable.';
            return $result;
        }

        if (!is_readable($file_path)) {
            $result['errors'][] = 'Le fichier CSV ne peut pas être lu.';
            return $result;
        }

        $file_size = filesize($file_path);

        if (false === $file_size) {
            $result['errors'][] = 'La taille du fichier CSV ne peut pas être déterminée.';
            return $result;
        }

        if (self::MAX_FILE_SIZE < $file_size) {
            $result['errors'][] = 'Le fichier CSV dépasse la taille maximale de 2 Mo.';
            return $result;
        }

        if (0 === $file_size) {
            $result['errors'][] = 'Le fichier CSV est vide.';
            return $result;
        }

        $handle = fopen($file_path, 'rb');

        if (false === $handle) {
            $result['errors'][] = 'Le fichier CSV ne peut pas être ouvert.';
            return $result;
        }

        $delimiter          = $this->detect_delimiter($handle);
        $result['delimiter'] = $delimiter;

        rewind($handle);
        $headers = fgetcsv($handle, 0, $delimiter, '"', '');

        if (false === $headers || $this->is_empty_row($headers)) {
            fclose($handle);
            $result['errors'][] = 'Le fichier CSV est vide ou ne contient aucun en-tête.';
            return $result;
        }

        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headers[0]);
        $headers    = array_map(
            static function ($header) {
                return trim((string) $header);
            },
            $headers
        );

        $result['headers'] = $headers;

        if (in_array('', $headers, true)) {
            $result['errors'][] = 'Un ou plusieurs noms de colonnes sont vides.';
        }

        $duplicate_headers = array_keys(
            array_filter(
                array_count_values($headers),
                static function ($count) {
                    return 1 < $count;
                }
            )
        );

        foreach ($duplicate_headers as $duplicate_header) {
            $result['errors'][] = sprintf(
                'La colonne « %s » est présente plusieurs fois.',
                $duplicate_header
            );
        }

        if (!empty($result['errors'])) {
            fclose($handle);
            return $result;
        }

        $data_row_number = 0;
        $csv_row_number  = 1;

        while (false !== ($values = fgetcsv($handle, 0, $delimiter, '"', ''))) {
            ++$csv_row_number;

            if ($this->is_empty_row($values)) {
                continue;
            }

            ++$data_row_number;

            if (self::MAX_ROWS < $data_row_number) {
                $result['errors'][] = 'Le fichier CSV contient plus de 500 lignes de données.';
                break;
            }

            if (count($headers) !== count($values)) {
                $result['errors'][] = sprintf(
                    'La ligne %d contient %d colonne(s) au lieu de %d.',
                    $csv_row_number,
                    count($values),
                    count($headers)
                );
                continue;
            }

            $values = array_map(
                static function ($value) {
                    return null === $value ? '' : (string) $value;
                },
                $values
            );

            $result['rows'][] = array_combine($headers, $values);
        }

        fclose($handle);

        return $result;
    }

    /**
     * Choisit le séparateur qui produit le plus de colonnes sur l'en-tête.
     *
     * @param resource $handle Ressource du fichier CSV.
     *
     * @return string
     */
    private function detect_delimiter($handle)
    {
        $best_delimiter = ';';
        $best_count     = 0;

        foreach ([';', ','] as $delimiter) {
            rewind($handle);
            $headers = fgetcsv($handle, 0, $delimiter, '"', '');
            $count   = is_array($headers) ? count($headers) : 0;

            if ($count > $best_count) {
                $best_count     = $count;
                $best_delimiter = $delimiter;
            }
        }

        return $best_delimiter;
    }

    /**
     * Indique si toutes les cellules d'une ligne sont vides.
     *
     * @param array $row Ligne CSV.
     *
     * @return bool
     */
    private function is_empty_row($row)
    {
        if (!is_array($row)) {
            return true;
        }

        foreach ($row as $value) {
            if ('' !== trim((string) $value)) {
                return false;
            }
        }

        return true;
    }
}
