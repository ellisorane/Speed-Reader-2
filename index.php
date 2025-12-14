<?php 
include "partials/header.php";
include "partials/notification.php";
include "config/Database.php";
include "classes/Pdf.php";
require 'vendor/autoload.php';
session_start();


if($_SERVER['REQUEST_METHOD'] === "POST") {

    if(isset($_POST['convert_file'])) {
        
        // Get uploaded file
        $uploaded_file = $_FILES['uploaded_file'];
        // var_dump($uploaded_file['type']);
        // Get file name and then remove the .pdf extension
        $file_path = $uploaded_file['tmp_name'];
        $filename = pathinfo($uploaded_file['name'], PATHINFO_FILENAME);

        // Convert pdf file  to text
        if($uploaded_file['type'] === 'application/pdf') {
    
            // Use PDF Parser
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($file_path);
    
            // Get an array of the all pages from pdf
            $pagesArr = $pdf->getPages();

        }
        
        // Convert epub files to text
        elseif($uploaded_file['type'] === 'application/epub+zip') {
            // var_dump(class_exists('ZipArchive'));
            $epub = new ZipArchive;
            // var_dump($epub);
            $text = '';
            if ($epub->open($file_path) === TRUE) {
                // var_dump($epub->statIndex(1));
                // var_dump($epub->getFromIndex(11));
                for ($i = 0; $i < $epub->numFiles; $i++) {
                    $file = $epub->statIndex($i);

                    // XHTML content is usually in OEBPS/*.xhtml or *.html
                    if (preg_match('/\.(xhtml|html)$/', $file['name'])) {
                        $content = $epub->getFromIndex($i);
                        $text .= strip_tags("PAGE-" . $i + 1 . " " . $content); // remove HTML
                    }
                    $text .= "<br><br>";
                }
                $epub->close();
            }

            echo $text;

        }

        else {
            echo "File type not supported.";
            $pagesArr = [];
        }
    }

}
?>

<h1>Speed Reader 2</h1>

<!-- Upload file to convert to text -->
<form action="" method="post" enctype="multipart/form-data">
    <label for="file"><strong>Add pdf</strong></label><br><br>
    <input type="file" name="uploaded_file" id="uploaded_file" name="uploaded_file"><br><br>
    <input type="submit" name="convert_file" value="Convert file">
</form>

<h3>Options</h3>
<!-- Start at -->
 <div>
     <label for="hidePages">Start reading at page</label>
     <input type="number" name="startAtPage" id="">
     <input type="submit" name="startReading" id="">
 </div>

<!-- Display file as text -->
<?php if($pagesArr) { ?>

    <h2>Converted Text</h2>
    <div class='textarea' contenteditable>
        <?php 
            $pagesArrIndex = 0;

            foreach($pagesArr as $page) {
                $pagesArrIndex++;
                $unclean_text = $page->getText(); 
                // Only show letters, numbers, punctuation (.,!?), and spaces
                $text = preg_replace('/[^a-zA-Z0-9 .,!?\-]/', '', $unclean_text);
                echo "PAGE-" . $pagesArrIndex . " " . $text . "<br><br>";
            }
         ?>
    </div>

<?php } ?>

<?php include "partials/footer.php"; ?>

    
