// JS for opening and closing .reading-area
const readingArea = document.querySelector('.reading-area')
const startReading = document.querySelector('.start-reading')
const closeReading = document.querySelector('.close-reading')
const textarea = document.querySelector('.textarea')
const currentWordDiv = document.querySelector('.current-word')

// Event listner - open reading interface
startReading.addEventListener('click', () => {
	readingArea.classList.add('reading-area-open')
})

// Event listner - close reading interface
closeReading.addEventListener('click', () => {
	readingArea.classList.remove('reading-area-open')
})

//////////////////////////////////////////////////////////////////////////////////////////////

// Store text from file in a JS variable
const text = document.querySelector('.textarea').innerHTML

// Split text into an array of words and remove spaces at beginning and end of text
let textArr = text.trim().split(/\s+/)
console.log(textArr)

// Get word and word position to display on .reading-area
let currentWordIndex = 0
let currentWord = textArr[currentWordIndex]

// Display currentWord as the first word in the text
currentWordDiv.innerHTML = currentWord

// Reading status - PAUSED or READING
let readingStatus = 'PAUSED'

// Used to manage setInterval in read()
let reading

// Pause reading
let pauseReading = () => {
	readingStatus = 'PAUSED'
	clearInterval(reading)
}

// Reading speed. Value is used to set the timing for setInterval in read()
// 60 wpm - 1 word per sec = 1000 ms
// 100 wpm - 1.67 word per sec =  1000/1.67 = 598 ms
// 120 wpm - 2 word per sec = 1000/2 = 500 ms
// 150 wpm - 2.5 word per sec = 1000/2.5 = 400 ms
// 200 wpm - 3.33 word per sec = 1000/3.33 = 300 ms (default)
// 250 wpm - 4.17 word per sec = 1000/4.17 = 240 ms
// 300 wpm - 5 word per sec = 1000/5 = 200 ms
// 350 wpm - 5.83 word per sec = 1000/5.83 = 172 ms
// 400 wpm - 6.67 word per sec = 1000/6.67 = 150 ms
// 450 wpm - 7.5 word per sec = 1000/7.5 = 133 ms
let speed = 300

// Event listner - Update reading speeding when <select> is changed
document.querySelector('#speed').addEventListener('change', (e) => {
	speed = e.target.value
	pauseReading()
})

// Start and Stop Reading. Also speed of reading
let read = (speed) => {
	if (readingStatus === 'PAUSED') {
		readingStatus = 'READING'

		// Start moving through textArr displaying the words that match the currentWordIndex index
		reading = setInterval(() => {
			currentWord = textArr[currentWordIndex]
			currentWordDiv.innerHTML = currentWord
			currentWordIndex++

			// Stop interval when at the end of the book.
			if (currentWordIndex >= textArr.length) {
				clearInterval(reading)
				readingStatus = 'PAUSED'
				currentWordIndex = textArr.length
				currentWordDiv.innerHTML = currentWord
			}

			return currentWordIndex
		}, speed)

		// Start/stop - Pause reading if it's already running
	} else if (readingStatus === 'READING') {
		pauseReading()
	}
}

// Event listner - start/stop reading
document.querySelector('.start-stop-reading').addEventListener('click', () => {
	read(speed)
})

// Skip forward or backward one word based on direction parameter (+ or -)
let skip = (direction) => {
	currentWord = textArr[currentWordIndex]
	if (currentWordIndex > 0 && direction === '-') {
		currentWordIndex--
	}
	if (currentWordIndex < textArr.length && direction === '+') {
		currentWordIndex++
	}
	if (readingStatus === 'READING') {
		pauseReading()
	}
	currentWordDiv.innerHTML = currentWord
}

// Event listner - skip forward by 1 word
document.querySelector('.forward').addEventListener('click', () => {
	skip('+')
})

// Event listner - go backward by 1 word
document.querySelector('.backward').addEventListener('click', () => {
	skip('-')
})

// Event Listner - Select starting word from Converted text section
textarea.addEventListener('click', () => {
	const selection = window.getSelection()
	if (!selection || selection.rangeCount === 0) return

	// Extend the selection to word boundaries
	selection.modify('move', 'backward', 'word')
	selection.modify('extend', 'forward', 'word')

	// Get selected text
	let selectedText = selection.toString()
	console.log(selectedText)

	// Array of text in selection area - Find index of selectedText and update currentWordIndex to match that of the selectedText.
})

// Editing Converted text

// Pasting text to read instead of uploading file

/////////////////////////////////////////////////////////////////////////////////////////////
