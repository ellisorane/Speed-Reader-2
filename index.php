<?php 
include "partials/header.php";
include "partials/notification.php";
include "config/Database.php";
include "classes/Pdf.php";
require 'vendor/autoload.php';
session_start();

// $uploaded_file = ''; 
// $file_path = '';
$pagesArr = [];
$nl = "<br>";

if($_SERVER['REQUEST_METHOD'] === "POST") {
    
    if(isset($_POST['convert_file'])) {
        $file_type = '';
        
        // Get uploaded file
        $uploaded_file = $_FILES['uploaded_file'];
        // var_dump($uploaded_file['type']);
        // Get file type. Later used in the 'Converted Text' section on the webpage - 'application/pdf'
        $file_type = $uploaded_file['type'];
        // Get file's temporary storage location
        $file_path = $uploaded_file['tmp_name'];
        // echo $file_path;
        // Get file name and then remove the .pdf extension (May need this later on)
        $filename = pathinfo($uploaded_file['name'], PATHINFO_FILENAME);

        // Convert pdf file  to text
        if($uploaded_file['type'] === 'application/pdf') {
    
            // Use Smalot PDF Parser
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
                        // $text .= strip_tags("PAGE-" . $i + 1 . " " . $content); // remove HTML
                        // $text .= "<br><br>";
                        $pagesArr[] = strip_tags($content); // remove HTML
                    }
                }
                $epub->close();
            }

        }
        // If file type is not pdf or epub
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
    <label for="file"><strong>Add a doc</strong></label><br><br>
    <input type="file" name="uploaded_file" id="uploaded_file" name="uploaded_file"><br><br>
    <input type="submit" name="convert_file" value="Convert file">
</form>

<?php 

// Only show .options if a file has been successfully uploaded
// Check if pdf or epub has been uploaded with no errors
if (isset($_FILES['uploaded_file']) && $_FILES['uploaded_file']['error'] == UPLOAD_ERR_OK) {

?>

    <div class="options">
        <h3>Options</h3>
        <!-- Start at -->
        <div>
            <label for="hidePages">Start reading at page</label>
            <input type="number" name="startAtPage" id="">
            <!-- Opens reading interface -->
            <div class="start-reading btn">Start Reading</div>
        </div>
    </div>

<?php } ?>


<?php 
// If $_POST['startReading'] then cover entire screen with speed reader display. 
// if ()

?>


<div class="reading-area">
    <h2>Controls</h2>

    <!-- Reading controls container -->
    <div class="reading-controls">
        <!-- Go Back - one word -->
        <div class="backwards btn">Back</div>
        <!-- start/stop -->
        <div class="start-stop-reading btn">Start/Stop</div> 
        <!-- Go forward - one word  -->
        <div class="forward btn">Forward</div>
        <!-- Reading Speed -->
        <div class="speed">
            <select name="speed" id="">
                <option value="">Reading speed - 100 wpm</option>
                <option value="">Reading speed - 150 wpm</option>
                <option value="">Reading speed - 200 wpm</option>
                <option value="">Reading speed - 250 wpm</option>
                <option value="">Reading speed - 300 wpm</option>
                <option value="">Reading speed - 350 wpm</option>
                <option value="">Reading speed - 400 wpm</option>
            </select>
        </div> 
        <!-- Close Reading area -->
        <div class="close-reading btn">X</div>

        <!-- Word currently being read -->
        <div class="current-word">This</div>
    </div>


</div>


<script>
// JS for opening and closing .reading-area
    const readingArea = document.querySelector('.reading-area');
    const startReading = document.querySelector('.start-reading');
    const closeReading = document.querySelector('.close-reading');


    startReading.addEventListener('click', () => {
        console.log('start reading');
        readingArea.classList.add('reading-area-open');
    })

    closeReading.addEventListener('click', () => {
        console.log('close reading');
        readingArea.classList.remove('reading-area-open');
    })

</script>

<!-- Display file as editable text -->
<?php if($pagesArr) { ?>

    <h2>Converted Text</h2>
    <div class='textarea' contenteditable>
        <?php 
            $pagesArrIndex = 0;

            foreach($pagesArr as $page) {
                $pagesArrIndex++;
                // PDFs
                if ($file_type === 'application/pdf') {
                    $unclean_text = $page->getText(); 
                    // Only show letters, numbers, punctuation (.,!?), and spaces
                    $text = preg_replace('/[^a-zA-Z0-9 .,!?\-]/', '', $unclean_text);
                    echo "PAGE-" . $pagesArrIndex . " " . $text . $nl . $nl;
                }
                // EPUBs
                elseif ($file_type == 'application/epub+zip') {
                    echo "PAGE-" . $pagesArrIndex . " " . $page . $nl . $nl;
                }
            }
         ?>
    </div>

<?php } else { ?>
    <p>Upload your pdf or epub document to get started.</p>

<?php } ?>

<?php include "partials/footer.php"; ?>

    
