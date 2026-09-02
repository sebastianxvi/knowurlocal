const csrfToken =
    document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content') || "";


let greeted = false;

let chatbox =
    document.getElementById("chatbox");

let lastUserMessage = "";


/*
 * Connect the paper-plane button to the
 * existing sendMessage() function.
 *
 * Using addEventListener keeps event handling
 * centralized instead of placing JavaScript
 * directly inside HTML attributes.
 */
const sendButton =
    document.querySelector(".chatbot-btn");


if (sendButton) {

    sendButton.addEventListener(
        "click",
        sendMessage
    );

}


const messageInput =
    document.getElementById("message");


if (messageInput) {

    messageInput.addEventListener(
        "keydown",
        function (e) {

            /*
             * Only submit when the Enter key is pressed.
             */
            if (e.key !== "Enter") {
                return;
            }

            /*
             * Prevent the browser from inserting a newline.
             */
            e.preventDefault();

            sendMessage();

        }
    );

}


/*
 * Escape HTML before inserting user-controlled
 * text into the DOM.
 *
 * This is an important XSS protection.
 */
function escapeHTML(str){

    return String(str ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;");

}


/*
 * Determine whether a URL is safe to use.
 *
 * Only HTTP and HTTPS URLs are allowed.
 *
 * javascript:, data:, file:, and similar protocols
 * are intentionally rejected.
 */
function isSafeUrl(value){

    if(
        typeof value !== "string" ||
        value.trim() === ""
    ){
        return false;
    }

    try {

        const url = new URL(
            value,
            window.location.origin
        );

        return (
            url.protocol === "https:" ||
            url.protocol === "http:"
        );

    } catch {

        return false;

    }

}


/*
 * Convert chatbot text into safe HTML.
 *
 * URLs are converted into safe external links while
 * the original text is HTML-escaped first.
 */
function allowSafeHTML(str){

    let escaped = escapeHTML(
        String(str ?? '')
    );

    escaped = escaped.replace(
        /https?:\/\/[^\s<]+/gi,
        function(rawUrl){

            const match = rawUrl.match(
                /^(.*?)([),.!?;:]*)$/
            );

            const url = match
                ? match[1]
                : rawUrl;

            const punctuation = match
                ? match[2]
                : '';

            if(!isSafeUrl(url)){
                return rawUrl;
            }

            return `<a href="${url}" target="_blank" rel="noopener noreferrer" class="chatbot-link">Open link <i class="ph-light ph-arrow-up-right"></i></a>${punctuation}`;

        }
    );

    return escaped;

}


// ================= LOAD SUGGESTIONS =================

async function loadSuggestions(){

    try{

        const res = await fetch(
            '/chat/suggestions',
            {
                method: 'GET',

                headers: {
                    'Accept': 'application/json'
                }
            }
        );

        /*
         * Do not process an unsuccessful HTTP response
         * as if it were valid chatbot data.
         */
        if(!res.ok){
            throw new Error(
                "Failed to fetch suggestions"
            );
        }

        const data =
            await res.json();

        renderSuggestions(data);

    }catch(err){

        console.error(
            "Suggestion error:",
            err
        );

    }

}


function renderSuggestions(questions){

    const container =
        document.getElementById(
            "chat-suggestions"
        );

    if (!container) {
        return;
    }

    container.innerHTML = "";

    if(!Array.isArray(questions)) {
        return;
    }

    const rows = [
        questions.slice(0, 5),
        questions.slice(5, 10),
        questions.slice(10, 15)
    ];

    rows.forEach(rowQuestions => {

        if(rowQuestions.length === 0) {
            return;
        }

        let row =
            document.createElement("div");

        row.classList.add(
            "suggestion-row"
        );

        let track =
            document.createElement("div");

        track.classList.add(
            "suggestion-track"
        );

        /*
         * Controlled duplication keeps the scrolling
         * suggestion track visually continuous.
         */
        let fullList = [
            ...rowQuestions,
            ...rowQuestions.slice(0, 3)
        ];

        fullList.forEach(q => {

            if(!q.question) {
                return;
            }

            let pill =
                document.createElement("button");

            pill.type = "button";

            pill.classList.add(
                "suggestion"
            );

            /*
             * textContent is intentionally used instead
             * of innerHTML because the FAQ question
             * originates from database content.
             */
            pill.textContent =
                q.question;

            pill.addEventListener(
                "click",
                () => {

                    const input =
                        document.getElementById(
                            "message"
                        );

                    if (!input) {
                        return;
                    }

                    input.value =
                        q.question;

                    input.focus();

                    sendMessage();

                }
            );

            track.appendChild(pill);

        });

        row.appendChild(track);

        container.appendChild(row);

    });

}


/*
 * Add a normal chatbot message to the conversation.
 */
function addMessage(
    text,
    type,
    isHTML = false
){

    let message =
        document.createElement("div");

    message.classList.add(
        "message",
        type
    );

    if(type === "user"){

        message.classList.add(
            "user-message-animate"
        );

    }

    let bubble =
        document.createElement("div");

    bubble.classList.add(
        "bubble"
    );

    if(isHTML){

        bubble.innerHTML =
            text.replace(
                /\n/g,
                "<br>"
            );

    }else{

        bubble.innerHTML =
            escapeHTML(text)
                .replace(
                    /\n/g,
                    "<br>"
                );

    }

    message.appendChild(
        bubble
    );

    chatbox.appendChild(
        message
    );

    chatbox.scrollTo({
        top: chatbox.scrollHeight,
        behavior: "smooth"
    });

    return message;

}


/*
 * Send a question to the human-support endpoint.
 */
async function sendToHuman(question){

    try{

        const res =
            await fetch(
                '/chat/support',
                {
                    method:'POST',

                    headers:{
                        'Content-Type':
                            'application/json',

                        'X-CSRF-TOKEN':
                            csrfToken
                    },

                    body: JSON.stringify({
                        question:
                            question,

                        agency_id:
                            agencyId
                    })
                }
            );

        if(res.status === 429){

            addMessage(
                "You're sending too fast. Please wait a moment.",
                "bot"
            );

            return null;
        }

        const data =
            await res.json();

        return data;

    }catch(err){

        console.error(
            "Support request error:",
            err
        );

        return null;

    }

}


/*
 * Send a selected clarification FAQ back to
 * the Laravel controller.
 *
 * The FAQ ID is treated only as a selector.
 * The server remains responsible for retrieving
 * and returning the approved answer.
 */
async function selectClarificationFaq(
    faqId,
    question
){

    /*
     * Reject invalid IDs before making a request.
     */
    const numericFaqId =
        Number(faqId);

    if(
        !Number.isInteger(numericFaqId) ||
        numericFaqId <= 0
    ){
        return;
    }

    /*
     * Remember the selected FAQ question.
     *
     * This is useful for support fallback and
     * conversation state.
     */
    lastUserMessage =
        question;

    /*
     * Display the selected option as the user's
     * chosen message.
     */
    addMessage(
        question,
        "user"
    );

    /*
     * Disable all clarification buttons immediately.
     *
     * This prevents double-clicking and sending
     * multiple identical requests.
     */
    document
        .querySelectorAll(
            ".chatbot-faq-choice"
        )
        .forEach(button => {

            button.disabled = true;

        });

    /*
     * Show the same typing indicator used by
     * normal chatbot requests.
     */
    const typingMessage =
        addMessage(
            `
            <div class="typing">
                <span></span>
                <span></span>
                <span></span>
            </div>
            `,
            "bot",
            true
        );

    try{

        const res =
            await fetch(
                '/chat',
                {
                    method: 'POST',

                    headers: {
                        'Content-Type':
                            'application/json',

                        'Accept':
                            'application/json',

                        'X-CSRF-TOKEN':
                            csrfToken
                    },

                    /*
                     * The FAQ ID tells the backend exactly
                     * which approved FAQ the user selected.
                     *
                     * The message is also sent so the request
                     * remains compatible with the existing
                     * chatbot validation.
                     */
                    body: JSON.stringify({
                        message:
                            question,

                        faq_id:
                            numericFaqId,

                        agency_id:
                            agencyId
                    })
                }
            );

        /*
         * Read the response as text first so malformed
         * Laravel responses can be diagnosed safely.
         */
        const text =
            await res.text();

        let data;

        try{

            data =
                JSON.parse(text);

        }catch(error){

            console.error(
                "Chatbot returned non-JSON response:",
                text
            );

            throw new Error(
                `Server returned HTTP ${res.status}`
            );

        }

        if(!res.ok){

            console.error(
                "FAQ selection API error:",
                data
            );

            throw new Error(
                data.message ||
                `FAQ selection failed (${res.status})`
            );

        }

        if(
            !data?.choices?.[0]?.message
        ){

            throw new Error(
                "Invalid chatbot response format."
            );

        }

        const messageData =
            data.choices[0].message;

        /*
         * Sanitize the approved answer before
         * inserting it into the chatbot bubble.
         */
        let html =
            allowSafeHTML(
                messageData.content || ''
            ).replace(
                /\n/g,
                "<br>"
            );

        /*
         * Preserve the existing FAQ-image behavior.
         */
        if(
            messageData.image &&
            isSafeUrl(messageData.image)
        ){

            const safeImageUrl =
                escapeHTML(
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

        /*
         * Replace the typing indicator with
         * the actual approved FAQ answer.
         */
        typingMessage
            .querySelector(".bubble")
            .innerHTML = html;

        chatbox.scrollTo({
            top:
                chatbox.scrollHeight,

            behavior:
                "smooth"
        });

    }catch(error){

        console.error(
            "FAQ selection error:",
            error
        );

        typingMessage
            .querySelector(".bubble")
            .textContent =
                "Sorry, something went wrong. Please try again.";

    }finally{

        /*
         * Re-enable the input regardless of whether
         * the request succeeded or failed.
         */
        if(messageInput){

            messageInput.disabled =
                false;

            messageInput.focus();

        }

    }

}


/*
 * Render clarification choices returned by Laravel.
 *
 * These become modern clickable capsules.
 */
function renderClarificationChoices(
    faqs
){

    /*
     * Only accept an actual array.
     */
    if(!Array.isArray(faqs)) {
        return;
    }

    /*
     * Only display the first two choices.
     *
     * The backend currently sends two candidates.
     */
    const choices =
        faqs.slice(0, 2);

    if(choices.length === 0) {
        return;
    }

    /*
     * Create a container for the choices.
     */
    const container =
        document.createElement("div");

    container.classList.add(
        "chatbot-faq-choices"
    );

    choices.forEach(faq => {

        /*
         * Ignore malformed FAQ records.
         */
        if(
            !faq ||
            !faq.id ||
            !faq.question
        ){
            return;
        }

        /*
         * Buttons are preferable to generic divs
         * because they provide native keyboard
         * accessibility.
         */
        const button =
            document.createElement("button");

        button.type =
            "button";

        button.classList.add(
            "chatbot-faq-choice"
        );

        /*
         * textContent prevents FAQ database content
         * from being interpreted as HTML.
         */
        button.textContent =
            faq.question;

        /*
         * Clicking the capsule selects that FAQ.
         */
        button.addEventListener(
            "click",
            () => {

                selectClarificationFaq(
                    faq.id,
                    faq.question
                );

            }
        );

        container.appendChild(
            button
        );

    });

    /*
     * Return the finished capsule group.
     */
    return container;

}


/*
 * Normal chatbot message submission.
 */
function sendMessage(){

    let input =
        document.getElementById(
            "message"
        );

    if(!input){
        return;
    }

    let message =
        input.value;

    lastUserMessage =
        message;

    /*
     * Do not send empty messages.
     */
    if(message.trim() === ""){
        return;
    }

    /*
     * Display the user's message immediately.
     */
    addMessage(
        message,
        "user"
    );

    /*
     * Clear the input field.
     */
    input.value = "";

    /*
     * Prevent duplicate submissions while the
     * current request is being processed.
     */
    input.disabled = true;

    /*
     * Show the typing animation.
     */
    let typingMessage =
        addMessage(
            `
            <div class="typing">
                <span></span>
                <span></span>
                <span></span>
            </div>
            `,
            "bot",
            true
        );


    fetch('/chat', {

        method: 'POST',

        headers: {

            'Content-Type':
                'application/json',

            'Accept':
                'application/json',

            'X-CSRF-TOKEN':
                csrfToken

        },

        body: JSON.stringify({

            message:
                message,

            agency_id:
                agencyId

        })

    })
    .then(async response => {

        /*
         * Read the response as text first.
         *
         * This allows us to diagnose Laravel HTML/error
         * responses without crashing on response.json().
         */
        const text =
            await response.text();

        let data;

        try {

            data =
                JSON.parse(text);

        } catch (error) {

            console.error(
                'Chatbot returned a non-JSON response:',
                text
            );

            throw new Error(
                `Server returned HTTP ${response.status}`
            );

        }

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
         * Verify that Laravel returned the expected
         * chatbot response structure.
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
         * Sanitize the chatbot's normal text response.
         */
        let html =
            allowSafeHTML(
                messageData.content || ''
            ).replace(
                /\n/g,
                "<br>"
            );


        /*
         * Show the human-support option when the
         * backend explicitly marks this as a fallback.
         */
        if (messageData.fallback) {

            html += `
                <div class="chat-fallback">
                    <button
                        type="button"
                        class="fallback-human-btn"
                    >
                        Talk to a human
                    </button>
                </div>
            `;

        }


        /*
         * Show an FAQ image only when the backend
         * supplied a safe URL.
         */
        if (
            messageData.image &&
            isSafeUrl(messageData.image)
        ) {

            const safeImageUrl =
                escapeHTML(
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


        /*
         * Replace the typing indicator with the
         * chatbot's text response.
         */
        const bubble =
            typingMessage.querySelector(
                ".bubble"
            );

        bubble.innerHTML =
            html;


        /*
         * If Laravel marked the response as a
         * clarification, render the FAQ capsules
         * underneath the clarification message.
         */
        if (
            messageData.clarification &&
            Array.isArray(messageData.faqs)
        ) {

            const choices =
                renderClarificationChoices(
                    messageData.faqs
                );

            /*
             * Append the buttons to the same bot bubble
             * so they visually belong to the clarification.
             */
            if(choices){

                bubble.appendChild(
                    choices
                );

            }

        }


        /*
         * Keep the latest chatbot content visible.
         */
        chatbox.scrollTo({

            top:
                chatbox.scrollHeight,

            behavior:
                "smooth"

        });


        /*
         * Restore the input after a successful response.
         */
        input.disabled =
            false;

        input.focus();

    })

    .catch(error => {

        console.error(
            "Chatbot request error:",
            error
        );

        /*
         * Show a generic error instead of exposing
         * internal server details to the user.
         */
        typingMessage
            .querySelector(".bubble")
            .textContent =
                "Sorry, something went wrong. Please try again.";

        /*
         * Always restore the input after failure.
         */
        input.disabled =
            false;

        input.focus();

    });

}


/*
 * Handle human-support buttons through event delegation.
 *
 * This works for dynamically-created fallback buttons.
 */
document.addEventListener(
    "click",
    async function(e){

        if(
            !e.target.classList.contains(
                "fallback-human-btn"
            )
        ){
            return;
        }

        let btn =
            e.target;

        /*
         * Prevent duplicate support submissions.
         */
        if(btn.disabled){
            return;
        }

        btn.disabled =
            true;

        let question =
            lastUserMessage;

        if(!question){

            addMessage(
                "Please type your question first.",
                "bot"
            );

            btn.disabled =
                false;

            return;
        }

        let data =
            await sendToHuman(
                question
            );

        if(data?.success){

            addMessage(
                "Your question has been sent to a human assistant.",
                "bot"
            );

        }else{

            addMessage(
                "Failed to send your request.",
                "bot"
            );

        }

        btn.disabled =
            false;

    }
);


/*
 * Chatbot open/close controls.
 */
const chatToggle =
    document.getElementById(
        "chat-toggle"
    );

const chatbot =
    document.getElementById(
        "chatbot"
    );

const overlay =
    document.getElementById(
        "chat-overlay"
    );


/*
 * Read the agency ID from the chatbot's data attribute.
 */
let agencyId =
    chatbot?.dataset.agency
        ? Number(
            chatbot.dataset.agency
        )
        : null;


/*
 * Read the agency name when supplied.
 */
let agencyName =
    chatbot?.dataset.agencyName ||
    null;


/*
 * Open the chatbot.
 */
if(chatToggle){

    chatToggle.addEventListener(
        "click",
        () => {

            chatbot.style.transform =
                "translateY(0)";

            chatbot.classList.add(
                "active"
            );

            overlay.classList.add(
                "active"
            );

            /*
             * Load suggestions only once per page visit.
             */
            if(!greeted){

                loadSuggestions();

                greeted =
                    true;

            }

        }
    );

}


/*
 * Clicking the overlay closes the chatbot.
 */
if(overlay){

    overlay.addEventListener(
        "click",
        closeChat
    );

}


function closeChat(){

    chatbot.classList.remove(
        "active"
    );

    overlay.classList.remove(
        "active"
    );

}


/*
 * Mobile drag-to-close behavior.
 */
const drag_to_close =
    document.getElementById(
        "drag-handle"
    );

let startY =
    0;

let currentY =
    0;

let dragging =
    false;


if(drag_to_close){

    drag_to_close.addEventListener(
        "touchstart",
        (e)=>{

            startY =
                e.touches[0].clientY;

            dragging =
                true;

        }
    );


    drag_to_close.addEventListener(
        "touchmove",
        (e)=>{

            if(!dragging){
                return;
            }

            currentY =
                e.touches[0].clientY;

            let move =
                currentY - startY;

            if(move > 0){

                chatbot.style.transform =
                    `translateY(${move}px)`;

            }

        }
    );


    drag_to_close.addEventListener(
        "touchend",
        ()=>{

            dragging =
                false;

            let move =
                currentY - startY;

            if(
                move >
                chatbot.offsetHeight * 0.50
            ){

                closeChat();

            }else{

                chatbot.style.transform =
                    "translateY(0)";

            }

        }
    );

}


/*
 * FAQ image modal.
 */
const imageModal =
    document.getElementById(
        "image-modal"
    );

const modalImg =
    document.getElementById(
        "modal-img"
    );

const imageClose =
    document.getElementById(
        "image-close"
    );


/*
 * Event delegation is used because FAQ images
 * are dynamically inserted after chatbot responses.
 */
document.addEventListener(
    "click",
    function(e){

        if(
            e.target.classList.contains(
                "clickable-image"
            )
        ){

            modalImg.src =
                e.target.src;

            imageModal.classList.add(
                "active"
            );

        }

    }
);


/*
 * Close the image modal.
 */
if(imageClose){

    imageClose.addEventListener(
        "click",
        () => {

            imageModal.classList.remove(
                "active"
            );

        }
    );

}


/*
 * Clicking outside the image also closes the modal.
 */
if(imageModal){

    imageModal.addEventListener(
        "click",
        (e)=>{

            if(
                e.target === imageModal
            ){

                imageModal.classList.remove(
                    "active"
                );

            }

        }
    );

}


/*
 * Close button inside the chatbot.
 */
const chatClose =
    document.getElementById(
        "chat-close"
    );


if(chatClose){

    chatClose.addEventListener(
        "click",
        function(e){

            e.stopPropagation();

            closeChat();

        }
    );

}


/*
 * Permanent "Ask a human" button.
 */
const askBtn =
    document.getElementById(
        "ask-human-btn"
    );


if(askBtn){

    askBtn.addEventListener(
        "click",
        async () => {

            /*
             * Prevent repeated clicks.
             */
            if(askBtn.disabled){
                return;
            }

            askBtn.disabled =
                true;

            let input =
                document.getElementById(
                    "message"
                );

            let question =
                input.value.trim() ||
                lastUserMessage;

            if(!question){

                addMessage(
                    "Please type your question first.",
                    "bot"
                );

                askBtn.disabled =
                    false;

                return;
            }

            let data =
                await sendToHuman(
                    question
                );

            if(data?.success){

                addMessage(
                    "Your question has been sent to a human assistant.",
                    "bot"
                );

                input.value =
                    "";

            }else{

                addMessage(
                    "Failed to send your request.",
                    "bot"
                );

            }

            askBtn.disabled =
                false;

        }
    );

}