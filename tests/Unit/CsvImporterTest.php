<?php
/**
 * Tests du lecteur CSV.
 */

class LPG_CSV_Importer_Test extends WP_UnitTestCase
{
    /**
     * Fichiers temporaires créés par le test courant.
     *
     * @var string[]
     */
    private $temporary_files = [];

    public function tear_down()
    {
        foreach ($this->temporary_files as $temporary_file) {
            if (file_exists($temporary_file)) {
                unlink($temporary_file);
            }
        }

        $this->temporary_files = [];
        parent::tear_down();
    }

    public function test_should_import_semicolon_csv_with_utf8_bom()
    {
        $file = $this->create_csv_file(
            "\xEF\xBB\xBFpage_title;slug;ville\nPage Paris;paris;Paris\nPage Lyon;lyon;Lyon\n"
        );

        $result = (new LPG_CSV_Importer())->import($file);

        $this->assertSame([], $result['errors']);
        $this->assertSame(';', $result['delimiter']);
        $this->assertSame(['page_title', 'slug', 'ville'], $result['headers']);
        $this->assertCount(2, $result['rows']);
        $this->assertSame('Paris', $result['rows'][0]['ville']);
    }

    public function test_should_detect_comma_delimiter()
    {
        $file   = $this->create_csv_file("page_title,slug,ville\nPage Lille,lille,Lille\n");
        $result = (new LPG_CSV_Importer())->import($file);

        $this->assertSame([], $result['errors']);
        $this->assertSame(',', $result['delimiter']);
        $this->assertSame('lille', $result['rows'][0]['slug']);
    }

    public function test_should_reject_rows_with_wrong_column_count()
    {
        $file   = $this->create_csv_file("page_title;slug;ville\nPage Paris;paris\n");
        $result = (new LPG_CSV_Importer())->import($file);

        $this->assertContains(
            'La ligne 2 contient 2 colonne(s) au lieu de 3.',
            $result['errors']
        );
        $this->assertSame([], $result['rows']);
    }

    public function test_should_reject_more_than_five_hundred_rows()
    {
        $lines = ['page_title;slug;ville'];

        for ($index = 1; $index <= 501; ++$index) {
            $lines[] = "Page {$index};page-{$index};Ville {$index}";
        }

        $file   = $this->create_csv_file(implode("\n", $lines));
        $result = (new LPG_CSV_Importer())->import($file);

        $this->assertContains(
            'Le fichier CSV contient plus de 500 lignes de données.',
            $result['errors']
        );
        $this->assertCount(500, $result['rows']);
    }

    /**
     * Crée un fichier CSV isolé pour un test.
     *
     * @param string $contents Contenu du fichier.
     *
     * @return string
     */
    private function create_csv_file($contents)
    {
        $temporary_file = tempnam(sys_get_temp_dir(), 'lpg-csv-');

        if (false === $temporary_file) {
            $this->fail('Impossible de créer le fichier CSV temporaire.');
        }

        file_put_contents($temporary_file, $contents);
        $this->temporary_files[] = $temporary_file;

        return $temporary_file;
    }
}

