const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

let greeted = false;
let chatbox=document.getElementById("chatbox");
let lastUserMessage = "";

document.getElementById("message").addEventListener("keydown",function(e){
    if(e.key==="Enter"){
        e.preventDefault();
        sendMessage();
    }
});

function escapeHTML(str){
    return str
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;");
}

function isSafeUrl(value){

    /*
     * Reject empty or non-string values immediately.
     */
    if(
        typeof value !== "string" ||
        value.trim() === ""
    ){
        return false;
    }

    try {

        /*
         * Resolve the supplied value into a real URL.
         * The current site is used as the base for relative URLs.
         */
        const url = new URL(
            value,
            window.location.origin
        );

        /*
         * Only allow normal web protocols.
         *
         * javascript:, data:, file:, and other
         * potentially dangerous protocols are rejected.
         */
        return (
            url.protocol === "https:" ||
            url.protocol === "http:"
        );

    } catch {

        /*
         * Invalid URL syntax is rejected.
         */
        return false;
    }
}


function allowSafeHTML(str){

    /*
     * Convert the chatbot response into a string.
     * This prevents errors if the backend returns null.
     */
    let escaped = escapeHTML(
        String(str ?? '')
    );

    /*
     * Detect only HTTP and HTTPS URLs.
     *
     * The URL is already HTML-escaped at this point,
     * so arbitrary HTML cannot be interpreted.
     */
    escaped = escaped.replace(
        /https?:\/\/[^\s<]+/gi,
        function(rawUrl){

            /*
             * Remove punctuation that belongs to the
             * surrounding sentence rather than the URL.
             */
            const match = rawUrl.match(
                /^(.*?)([),.!?;:]*)$/
            );

            const url = match
                ? match[1]
                : rawUrl;

            const punctuation = match
                ? match[2]
                : '';

            /*
             * Validate the URL before generating
             * an interactive link.
             */
            if(!isSafeUrl(url)){
                return rawUrl;
            }

            /*
             * Generate the link ourselves.
             *
             * The destination is already escaped above.
             */
            return `<a href="${url}" target="_blank" rel="noopener noreferrer" class="chatbot-link">Open link <i class="ph-light ph-arrow-up-right"></i></a>${punctuation}`;
        }
    );

    return escaped;
}

// ================= LOAD SUGGESTIONS =================
async function loadSuggestions(){

    try{
        const res = await fetch('/chat/suggestions', {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });

        // 🔒 security: check response status
        if(!res.ok){
            throw new Error("Failed to fetch suggestions");
        }

        const data = await res.json();

        renderSuggestions(data);

    }catch(err){
        console.error("Suggestion error:", err);
    }

}

function renderSuggestions(questions){

    const container = document.getElementById("chat-suggestions");
    container.innerHTML = "";

    if(!Array.isArray(questions)) return;

    const rows = [
        questions.slice(0, 5),
        questions.slice(5, 10),
        questions.slice(10, 15)
    ];

    rows.forEach(rowQuestions => {

        if(rowQuestions.length === 0) return;

        let row = document.createElement("div");
        row.classList.add("suggestion-row");

        let track = document.createElement("div");
        track.classList.add("suggestion-track");

        // 🔥 controlled duplication (not too wide)
        let fullList = [...rowQuestions, ...rowQuestions.slice(0, 3)];


        fullList.forEach(q => {

            if(!q.question) return;

            let pill = document.createElement("button");

pill.type = "button";
pill.classList.add("suggestion");
pill.textContent = q.question;

            pill.addEventListener("click", () => {
                const input = document.getElementById("message");
                input.value = q.question;
                input.focus();
                sendMessage();
            });

            track.appendChild(pill);
        });

        row.appendChild(track);
        container.appendChild(row);
    });
}


function addMessage(text, type, isHTML = false){


    let message=document.createElement("div");
    message.classList.add("message",type);
    if(type === "user"){
       message.classList.add("user-message-animate");
    }

    let bubble=document.createElement("div");
    bubble.classList.add("bubble");
    
    if(isHTML){
    bubble.innerHTML = text.replace(/\n/g, "<br>");
    }else{
        bubble.innerHTML = escapeHTML(text).replace(/\n/g, "<br>");
    }

    message.appendChild(bubble);
    chatbox.appendChild(message);

    chatbox.scrollTo({
        top: chatbox.scrollHeight,
        behavior: "smooth"
    });

    return message;
}

async function sendToHuman(question){

    try{
        const res = await fetch('/chat/support', {
            method:'POST',
            headers:{
                'Content-Type':'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                question: question,
                agency_id: agencyId
            })
        });

        if(res.status === 429){
            addMessage("You're sending too fast. Please wait a moment.", "bot");
            return null;
        }

        const data = await res.json();
        return data;

    }catch(err){
        console.error("Support request error:", err);
        return null;
    }
}

function sendMessage(){

    let input=document.getElementById("message");
    let message=input.value;
    lastUserMessage = message;

    if(message.trim()=="") return;

    addMessage(message,"user");

    input.value="";

    input.disabled = true;

    // show typing indicator
    let typingMessage = addMessage(`
        <div class="typing">
        <span></span>
        <span></span>
        <span></span>
        </div>
    `, "bot", true);

    fetch('/chat', {
    method: 'POST',

    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken
    },

    body: JSON.stringify({
        message: message,
        agency_id: agencyId
    })
})
.then(async response => {

    /*
     * Read the response as text first.
     *
     * This lets us safely inspect both JSON responses
     * and Laravel error responses.
     */
    const text = await response.text();

    let data;

    try {
        data = JSON.parse(text);
    } catch (error) {

        console.error(
            'Chatbot returned a non-JSON response:',
            text
        );

        throw new Error(
            `Server returned HTTP ${response.status}`
        );
    }

    /*
     * Treat HTTP errors as actual errors.
     */
    if (!response.ok) {

        console.error(
            'Chatbot API error:',
            data
        );

        throw new Error(
            data.message ||
            `Chatbot request failed (${response.status})`
        );
    }

    return data;
})
.then(data => {

    /*
     * Validate the expected response structure before
     * accessing nested properties.
     */
    if (
        !data?.choices?.[0]?.message
    ) {
        throw new Error(
            'Invalid chatbot response format.'
        );
    }

    const messageData =
    data.choices[0].message;



/*
 * Sanitize chatbot text before inserting it
 * into the DOM.
 */
let html = allowSafeHTML(
    messageData.content || ''
).replace(/\n/g, "<br>");


    /*
     * Show human-support option when the backend
     * explicitly requests fallback.
     */
    if (messageData.fallback) {

        html += `
            <div class="chat-fallback">
                <button class="fallback-human-btn">
                    Talk to a human
                </button>
            </div>
        `;
    }

    /*
     * Only display an image when the backend supplied one.
     */
    if (
    messageData.image &&
    isSafeUrl(messageData.image)
) {

    const safeImageUrl = escapeHTML(
        messageData.image
    );

    html += `
        <div class="chat-image">
            <img
                src="${safeImageUrl}"
                alt="FAQ Image"
                class="clickable-image"
                loading="lazy"
                referrerpolicy="no-referrer"
            >
        </div>
    `;
}

    typingMessage
        .querySelector(".bubble")
        .innerHTML = html;

    chatbox.scrollTo({
        top: chatbox.scrollHeight,
        behavior: "smooth"
    });

    input.disabled = false;
    input.focus();
})
.catch(error => {

    console.error(
        "Chatbot request error:",
        error
    );

    typingMessage
        .querySelector(".bubble")
        .textContent =
            "Sorry, something went wrong. Please try again.";

    /*
     * IMPORTANT:
     *
     * Always restore the input after an error.
     * Otherwise one failed request permanently disables
     * the chatbot input.
     */
    input.disabled = false;
    input.focus();
});

}

document.addEventListener("click", async function(e){

    if(e.target.classList.contains("fallback-human-btn")){

        let btn = e.target;

        if(btn.disabled) return;
        btn.disabled = true;

        let question = lastUserMessage;

        if(!question){
            addMessage("Please type your question first.", "bot");
            btn.disabled = false;
            return;
        }

        let data = await sendToHuman(question);

        if(data?.success){
            addMessage("✅ Your question has been sent to a human assistant.", "bot");
        }else{
            addMessage("❌ Failed to send your request.", "bot");
        }

        btn.disabled = false;
    }

});


const chatToggle = document.getElementById("chat-toggle");
const chatbot = document.getElementById("chatbot");
const overlay = document.getElementById("chat-overlay");


let agencyId = null;
let agencyName = chatbot?.dataset.agencyName || null;

if(chatbot){
    agencyId = chatbot.dataset.agency;
    let agencyName = chatbot?.dataset.agencyName || null;
}



chatToggle.addEventListener("click", () => {

    chatbot.style.transform = "translateY(0)";
    chatbot.classList.add("active");
    overlay.classList.add("active");

    // ✅ load suggestions instead of old greeting
    if(!greeted){
        loadSuggestions();
        greeted = true;
    }

});

overlay.addEventListener("click", closeChat);

function closeChat(){

    chatbot.classList.remove("active");
    overlay.classList.remove("active");

}

const drag_to_close = document.getElementById("drag-handle");

let startY = 0;
let currentY = 0;
let dragging = false;

drag_to_close.addEventListener("touchstart",(e)=>{

    startY = e.touches[0].clientY;
    dragging = true;

});

drag_to_close.addEventListener("touchmove",(e)=>{

    if(!dragging) return;

    currentY = e.touches[0].clientY;
    let move = currentY - startY;

    if(move > 0){

        chatbot.style.transform = `translateY(${move}px)`;

    }

});

drag_to_close.addEventListener("touchend",()=>{

    dragging = false;

    let move = currentY - startY;

    if(move > chatbot.offsetHeight * 0.50){

        closeChat();

    }else{
        chatbot.style.transform = "translateY(0)";
    }

});

const imageModal = document.getElementById("image-modal");
const modalImg = document.getElementById("modal-img");
const imageClose = document.getElementById("image-close");

// 🔥 use event delegation (important for dynamic content)
document.addEventListener("click", function(e){

    if(e.target.classList.contains("clickable-image")){
        modalImg.src = e.target.src;
        imageModal.classList.add("active");
    }

});

// close modal
imageClose.addEventListener("click", () => {
    imageModal.classList.remove("active");
});

// click outside image closes modal
imageModal.addEventListener("click", (e)=>{
    if(e.target === imageModal){
        imageModal.classList.remove("active");
    }
});

const chatClose = document.getElementById("chat-close");

chatClose.addEventListener("click", function(e){
    e.stopPropagation();
    closeChat();
});

const askBtn = document.getElementById("ask-human-btn");

askBtn.addEventListener("click", async () => {

    if(askBtn.disabled) return; // 🔒 prevent spam
    askBtn.disabled = true;

    let input = document.getElementById("message");
    let question = input.value.trim() || lastUserMessage;

    if(!question){
        addMessage("Please type your question first.", "bot");
        askBtn.disabled = false;
        return;
    }

    let data = await sendToHuman(question);

    if(data?.success){
        addMessage("✅ Your question has been sent to a human assistant.", "bot");
        input.value = "";
    }else{
        addMessage("❌ Failed to send your request.", "bot");
    }

    askBtn.disabled = false; // 🔓 re-enable
});