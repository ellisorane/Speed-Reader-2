<?php 
include "partials/header.php";
include "partials/notification.php";
include "config/Database.php";
include "classes/Pdf.php";
require 'vendor/autoload.php';
session_start();

$database = new Database();
$db = $database->connect();

// $uploaded_file = ''; 
// $file_path = '';
$pagesArr = [];
$nl = "\n \n";

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



<div class="reading-area">
    <!-- .reading-area is hidden until .start reading is pressed -->

    <h2>Controls</h2>

    <!-- Reading controls container -->
    <div class="reading-controls">
        <!-- Go Back - one word -->
        <div class="backwards btn">Back</div>
        <!-- start/stop -->
        <div class="start-stop-reading btn" onclick="read()">Start/Stop</div> 
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
        <div class="current-word"></div>
    </div>


</div>



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
                    // echo "PAGE-" . $pagesArrIndex . " " . $text . $nl . $nl;
                    echo $text . $nl . $nl;
                }
                // EPUBs
                elseif ($file_type == 'application/epub+zip') {
                    // echo "PAGE-" . $pagesArrIndex . " " . $page . $nl . $nl;
                    echo $page . $nl . $nl;
                }
            }
         ?>
    </div>

<?php } else { ?>
    <p>Upload your pdf or epub document to get started.</p>

<?php } ?>  


<script>

    // JS for opening and closing .reading-area
    const readingArea = document.querySelector('.reading-area');
    const startReading = document.querySelector('.start-reading');
    const closeReading = document.querySelector('.close-reading');
    const textarea = document.querySelector('.textarea')
    const currentWordDiv = document.querySelector('.current-word');

    startReading.addEventListener('click', () => {
        // console.log('start reading');
        readingArea.classList.add('reading-area-open');
    })

    closeReading.addEventListener('click', () => {
        // console.log('close reading');
        readingArea.classList.remove('reading-area-open');
    })

    //////////////////////////////////////////////////////////////////////////////////////////////

    // Store text from file in a JS variable
    const text = document.querySelector('.textarea').innerHTML; 
    // console.log(text);

    // Split text into an array of pages
    const pages = text.split("<br><br>");
    // const pages = text.split("<br><br>");
    console.log(pages);

    // Get word and word position to display on .reading-area
    let currentPageIndex = 0;
    let currentWordIndex = 0;

    // Split current page into an array of words
    // let currentPageArr = pages[currentPage].split(/\s+/);
    let currentPageArr;
    let currentWord;

    // console.log(currentPageArr);
    
    

    // Read function 
    let read = () => {

        currentPageIndex = 0;
        currentWordIndex = 0;


        // Start moving through currentPageArr displaying the words that match the currentWordIndex index and then stop when stop when at the end of page 
        let reading = setInterval(() => {
            currentPageArr = pages[currentPageIndex].trim().split(/\s+/);
            console.log(currentPageArr);
            currentWord = currentPageArr[currentWordIndex];
            currentWordDiv.innerHTML = currentWord;
            currentWordIndex ++;
            // console.log('Word index: ' + currentWordIndex);

            
            // Go to next page once current page is done being read and reset currentWordIndex
            if(currentWordIndex >= currentPageArr.length - 1 && currentPageIndex < pages.length - 1) {
                console.log("Page Index: " + currentPageIndex);
                currentWordIndex = 0;
                currentPageIndex ++;

            } 
            // Stop interval when at the end of the book. 
            if(currentWordIndex >= currentPageArr.length - 1 && currentPageIndex >= pages.length - 1) {

                clearInterval(reading);
                console.log("The interval has been stopped.");
                currentPageIndex = pages.length - 1;
                currentWordIndex = currentPageArr.length - 1;
                console.log("Last word: " + currentWord + " - " + currentWordIndex);
                console.log("next word: " + currentWord + " - " + (currentWordIndex + 1));
                currentWordDiv.innerHTML = currentWord;
            }
        }, 10 );


        
    }

    /////////////////////////////////////////////////////////////////////////////////////////////


</script>


<?php include "partials/footer.php"; ?>

    
