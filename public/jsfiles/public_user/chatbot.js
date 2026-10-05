const csrfToken =
    document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content') || "";

const chatbotFeedbackUrl =
    document.querySelector('meta[name="chatbot-feedback-url"]')
        ?.getAttribute('content') || '/chat/feedback';

function renderFaqFeedback(logId) {
    const id = Number(logId);
    if (!Number.isInteger(id) || id < 1) return '';

    return `
        <section class="chat-feedback" data-feedback-log-id="${id}" aria-label="Rate this FAQ answer">
            <div class="chat-feedback-prompt">Was this answer helpful?</div>
            <div class="chat-feedback-actions">
                <button type="button" class="chat-feedback-vote" data-rating="helpful" aria-label="This answer was helpful">
                    <i class="ph-light ph-thumbs-up" aria-hidden="true"></i>
                    <span>Helpful</span>
                </button>
                <button type="button" class="chat-feedback-vote" data-rating="not_helpful" aria-label="This answer was not helpful">
                    <i class="ph-light ph-thumbs-down" aria-hidden="true"></i>
                    <span>Needs work</span>
                </button>
            </div>
            <form class="chat-feedback-form" hidden>
                <label class="chat-feedback-label">
                    What could be improved? <span>(optional)</span>
                    <select name="reason">
                        <option value="">Choose a reason</option>
                        <option value="incorrect">Information seems incorrect</option>
                        <option value="incomplete">Answer is incomplete</option>
                        <option value="outdated">Information may be outdated</option>
                        <option value="unclear">Answer is confusing</option>
                        <option value="attachments">Links or attachments do not work</option>
                        <option value="other">Other</option>
                    </select>
                </label>
                <label class="chat-feedback-label">
                    Additional details <span>(optional)</span>
                    <textarea name="comment" rows="2" maxlength="1000" placeholder="Tell us what needs attention…"></textarea>
                </label>
                <div class="chat-feedback-form-actions">
                    <button type="button" class="chat-feedback-cancel">Cancel</button>
                    <button type="submit" class="chat-feedback-submit">Send feedback</button>
                </div>
            </form>
            <div class="chat-feedback-status" role="status" aria-live="polite" hidden></div>
        </section>
    `;
}


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


/*
 * Auto-grow the composer while preserving a compact one-line default.
 * Shift+Enter inserts a newline; Enter sends the message.
 */
function resizeMessageInput() {
    if (!messageInput) return;

    messageInput.style.height = "auto";
    const maxHeight = 120;
    messageInput.style.height = `${Math.min(messageInput.scrollHeight, maxHeight)}px`;
    messageInput.style.overflowY = messageInput.scrollHeight > maxHeight ? "auto" : "hidden";
}

if (messageInput) {
    messageInput.addEventListener("input", resizeMessageInput);
    messageInput.addEventListener("keydown", function (e) {
        if (e.key !== "Enter" || e.shiftKey || e.isComposing) {
            return;
        }

        e.preventDefault();
        sendMessage();
    });

    resizeMessageInput();
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
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");

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

        const section = document.getElementById('chat-suggestion-section');
        if (section) section.hidden = true;

    }

}



function renderFaqImages(attachments) {
    if (!Array.isArray(attachments)) return '';

    return attachments
        .filter((attachment) => attachment?.type === 'image')
        .map((attachment) => {
            const label = escapeHTML(attachment?.label || 'FAQ image');
            const url = attachment?.url;

            if (!url || !isSafeUrl(url)) return '';

            const safeUrl = escapeHTML(url);

            return `
                <figure class="chat-faq-image-attachment">
                    <button
                        type="button"
                        class="chat-faq-image-button"
                        data-image-url="${safeUrl}"
                        aria-label="Open ${label} image"
                    >
                        <img
                            src="${safeUrl}"
                            alt="${label}"
                            class="chat-faq-inline-image clickable-image"
                            loading="lazy"
                            referrerpolicy="no-referrer"
                        >
                    </button>
                    ${label !== 'FAQ image' ? `<figcaption>${label}</figcaption>` : ''}
                </figure>
            `;
        })
        .join('');
}

function renderFaqFilesAndLinks(attachments) {
    if (!Array.isArray(attachments)) return '';

    return attachments
        .filter((attachment) => ['file', 'link'].includes(attachment?.type))
        .map((attachment) => {
            const label = escapeHTML(attachment?.label || 'Attachment');
            const url = attachment?.url;
            const type = attachment?.type;

            if (!url || !isSafeUrl(url)) return '';

            const safeUrl = escapeHTML(url);
            const visibleText = label !== 'Attachment' ? label : url;

            if (type === 'file') {
                return `
                    <a
                        class="chat-faq-file-link"
                        href="${safeUrl}"
                        download
                        aria-label="Download ${label}"
                    >
                        <i class="ph-light ph-file-arrow-down"></i>
                        <span>${visibleText}</span>
                        <i class="ph-light ph-download-simple"></i>
                    </a>
                `;
            }

            return `
                <a
                    class="chat-faq-plain-link"
                    href="${safeUrl}"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    ${visibleText}
                    <i class="ph-light ph-arrow-up-right"></i>
                </a>
            `;
        })
        .join('');
}

function renderFaqQRAttachments(attachments) {
    if (!Array.isArray(attachments)) return '';

    return attachments
        .filter((attachment) => attachment?.type === 'qr_code')
        .map((attachment, index) => {
            const label = escapeHTML(attachment?.label || 'QR code');
            const url = attachment?.url;

            if (!url || !isSafeUrl(url)) return '';

            const safeUrl = escapeHTML(url);
            const qrId = `chat-faq-qr-${Date.now()}-${index}-${Math.random().toString(36).slice(2, 8)}`;

            return `
                <div class="chat-faq-qr-section">
                    <div class="chat-faq-qr-heading">
                        <i class="ph-light ph-qr-code"></i>
                        <span>${label}</span>
                    </div>
                    <div
                        id="${qrId}"
                        class="chat-faq-qr"
                        data-qr-value="${safeUrl}"
                        aria-label="${label} QR code"
                    ></div>
                    <a
                        href="${safeUrl}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="chat-faq-qr-open"
                    >
                        Open destination
                        <i class="ph-light ph-arrow-up-right"></i>
                    </a>
                </div>
            `;
        })
        .join('');
}

function renderFaqAttachments(attachments) {
    if (!Array.isArray(attachments) || attachments.length === 0) return '';

    return `
        <div class="chat-faq-attachments" aria-label="FAQ attachments">
            ${renderFaqImages(attachments)}
            <div class="chat-faq-file-link-list">
                ${renderFaqFilesAndLinks(attachments)}
            </div>
            ${renderFaqQRAttachments(attachments)}
        </div>
    `;
}

/**
 * Render QR codes with a reliable image-service fallback.
 *
 * The FAQ database remains the only source of the QR destination.
 * The QR service only turns that existing URL into pixels; it does not
 * generate, alter, or select any KNOWURLOCAL response content.
 */
function renderFaqQRCodes(root = document) {
    root.querySelectorAll('.chat-faq-qr[data-qr-value]').forEach((container) => {
        if (container.dataset.qrRendered === '1') return;

        const value = container.dataset.qrValue;
        if (!value || !isSafeUrl(value)) return;

        const encoded = encodeURIComponent(value);
        const externalQrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=220x220&margin=8&data=${encoded}`;

        const img = document.createElement('img');
        img.className = 'chat-faq-qr-image';
        img.alt = container.getAttribute('aria-label') || 'Scannable QR code';
        img.width = 180;
        img.height = 180;
        img.loading = 'eager';
        img.decoding = 'async';
        img.referrerPolicy = 'no-referrer';

        img.addEventListener('load', () => {
            container.replaceChildren(img);
            container.dataset.qrRendered = '1';
        }, { once: true });

        img.addEventListener('error', () => {
            // Keep a deterministic local fallback when the image service is
            // unavailable. This also makes failures visible instead of
            // leaving an empty white QR box.
            const QRCore = window.QRCodeCore;

            if (typeof QRCore !== 'function') {
                container.innerHTML = `
                    <a
                        href="${escapeHTML(value)}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="chat-faq-qr-fallback"
                    >
                        Open QR destination
                    </a>
                `;
                return;
            }

            try {
                let qr = null;
                let lastError = null;

                for (const level of [
                    QRCore.CorrectLevel?.M,
                    QRCore.CorrectLevel?.L
                ]) {
                    if (level === undefined) continue;

                    try {
                        const candidate = new QRCore(0, level);
                        candidate.addData(value);
                        candidate.make();
                        qr = candidate;
                        break;
                    } catch (error) {
                        lastError = error;
                    }
                }

                if (!qr) throw lastError || new Error('Unable to encode QR data.');

                const moduleCount = qr.getModuleCount();
                const quietZone = 4;
                const viewSize = moduleCount + (quietZone * 2);
                const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');

                svg.setAttribute('viewBox', `0 0 ${viewSize} ${viewSize}`);
                svg.setAttribute('role', 'img');
                svg.setAttribute('aria-label', container.getAttribute('aria-label') || 'Scannable QR code');
                svg.setAttribute('shape-rendering', 'crispEdges');
                svg.classList.add('chat-faq-qr-svg');

                const background = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                background.setAttribute('width', String(viewSize));
                background.setAttribute('height', String(viewSize));
                background.setAttribute('fill', '#ffffff');
                svg.appendChild(background);

                for (let row = 0; row < moduleCount; row++) {
                    for (let col = 0; col < moduleCount; col++) {
                        if (!qr.isDark(row, col)) continue;
                        const module = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                        module.setAttribute('x', String(col + quietZone));
                        module.setAttribute('y', String(row + quietZone));
                        module.setAttribute('width', '1');
                        module.setAttribute('height', '1');
                        module.setAttribute('fill', '#111827');
                        svg.appendChild(module);
                    }
                }

                container.replaceChildren(svg);
                container.dataset.qrRendered = '1';
            } catch (error) {
                console.error('Failed to render FAQ QR code:', error);
                container.innerHTML = `
                    <a
                        href="${escapeHTML(value)}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="chat-faq-qr-fallback"
                    >
                        Open QR destination
                    </a>
                `;
            }
        }, { once: true });

        img.src = externalQrUrl;
    });
}

function dismissChatWelcome() {
    const welcome = document.getElementById('chat-welcome');
    if (!welcome || welcome.classList.contains('is-dismissed')) return;

    welcome.classList.add('is-dismissed');
    welcome.setAttribute('aria-hidden', 'true');
}

function renderSuggestions(questions) {
    const container = document.getElementById('chat-suggestions');
    const section = document.getElementById('chat-suggestion-section');

    if (!container || !section) return;

    container.replaceChildren();

    if (!Array.isArray(questions)) {
        section.hidden = true;
        return;
    }

    // The endpoint already orders answered/popular FAQs first, followed by recent FAQs.
    // Only show three distinct database questions in the welcome state.
    const seen = new Set();
    const curatedQuestions = questions.filter((item) => {
        const question = typeof item?.question === 'string' ? item.question.trim() : '';
        const key = question.toLocaleLowerCase();
        if (!question || seen.has(key)) return false;
        seen.add(key);
        return true;
    }).slice(0, 3);

    if (curatedQuestions.length === 0) {
        section.hidden = true;
        return;
    }

    const icons = ['ph-file-text', 'ph-buildings', 'ph-clipboard-text'];

    curatedQuestions.forEach((item, index) => {
        const question = item.question.trim();
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'suggestion';
        button.setAttribute('aria-label', `Ask: ${question}`);

        const icon = document.createElement('i');
        icon.className = `ph-light ${icons[index] || 'ph-chat-circle-text'} suggestion-icon`;
        icon.setAttribute('aria-hidden', 'true');

        const label = document.createElement('span');
        label.className = 'suggestion-label';
        label.textContent = question;

        const arrow = document.createElement('i');
        arrow.className = 'ph-light ph-arrow-up-right suggestion-arrow';
        arrow.setAttribute('aria-hidden', 'true');

        button.append(icon, label, arrow);
        button.addEventListener('click', () => {
            const input = document.getElementById('message');
            if (!input || input.disabled) return;

            input.value = question;
            if (typeof resizeMessageInput === 'function') resizeMessageInput();
            dismissChatWelcome();
            sendMessage();
        });

        container.appendChild(button);
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

    const message =
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

    const bubble =
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
 * Show the assistant's loading state without putting it inside a message
 * bubble. Only actual user and assistant responses receive bubble styling.
 */
function addTypingIndicator(){

    const message =
        document.createElement("div");

    message.classList.add(
        "message",
        "bot",
        "is-loading"
    );

    message.setAttribute(
        "role",
        "status"
    );

    message.setAttribute(
        "aria-live",
        "polite"
    );

    message.innerHTML = `
        <div class="chatbot-typing-indicator">
            <span class="chatbot-typing-dots" aria-hidden="true">
                <span></span>
                <span></span>
                <span></span>
            </span>
            <span class="chatbot-typing-label">Matching FAQs</span>
        </div>
    `;

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
 * Turn a standalone loading row into a normal assistant response bubble.
 */
function setBotMessageContent(message, html){

    message.classList.remove(
        "is-loading"
    );

    message.removeAttribute(
        "role"
    );

    message.removeAttribute(
        "aria-live"
    );

    message.replaceChildren();

    const bubble =
        document.createElement("div");

    bubble.className =
        "bubble";

    bubble.innerHTML =
        html;

    message.appendChild(
        bubble
    );

    return bubble;
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

        if (res.status === 429) {
            return {
                success: false,
                message: "You're sending too fast. Please wait a moment."
            };
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
 * All ticket entry points share one in-flight guard. This prevents duplicate
 * requests even if the header, mobile, and fallback actions are clicked quickly.
 */
let ticketSubmissionInProgress = false;

function setTicketButtonsLoading(isLoading) {
    const buttons = document.querySelectorAll(
        "#ask-human-btn, #ask-human-mobile, .fallback-human-btn"
    );

    buttons.forEach((button) => {
        if (isLoading) {
            if (!button.dataset.idleHtml) {
                button.dataset.idleHtml = button.innerHTML;
            }

            button.disabled = true;
            button.classList.add("is-loading");
            button.setAttribute("aria-busy", "true");
            button.innerHTML = `
                <span class="ticket-loading-spinner" aria-hidden="true"></span>
                <span>Sending...</span>
            `;
        } else {
            button.disabled = false;
            button.classList.remove("is-loading");
            button.removeAttribute("aria-busy");

            if (button.dataset.idleHtml) {
                button.innerHTML = button.dataset.idleHtml;
                delete button.dataset.idleHtml;
            }
        }
    });
}

async function submitTicket(question = "", sourceButton = null) {
    if (ticketSubmissionInProgress) return;

    const input = document.getElementById("message");
    const resolvedQuestion = String(
        question || input?.value?.trim() || lastUserMessage || ""
    ).trim();

    if (!resolvedQuestion) {
        addMessage("Please type your question first.", "bot");
        input?.focus();
        return;
    }

    ticketSubmissionInProgress = true;
    setTicketButtonsLoading(true);

    try {
        const data = await sendToHuman(resolvedQuestion);

        if (data?.success) {
            addMessage(
                data.message || "Your question has been sent to a human assistant.",
                "bot"
            );

            if (input) {
                input.value = "";
                resizeMessageInput();
            }
        } else {
            addMessage(
                data?.message || "We couldn't send your ticket. Please try again.",
                "bot"
            );
        }
    } catch (error) {
        console.error("Ticket submission failed:", error);
        addMessage("We couldn't send your ticket. Please try again.", "bot");
    } finally {
        ticketSubmissionInProgress = false;
        setTicketButtonsLoading(false);
        sourceButton?.focus?.();
    }
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

    // Once a real question is submitted, let the conversation take over the welcome state.
    dismissChatWelcome();

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
    resizeMessageInput();

    /*
     * Prevent duplicate submissions while the
     * current request is being processed.
     */
    input.disabled = true;

    /*
     * Show the typing animation.
     */
    const typingMessage =
        addTypingIndicator();


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

            const serverMessage =
                data?.choices?.[0]?.message?.content ||
                data?.message ||
                `Chatbot request failed (${response.status})`;

            const serverError =
                new Error(serverMessage);

            serverError.serverMessage =
                serverMessage;

            throw serverError;

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
        const attachments = Array.isArray(messageData.attachments)
            ? messageData.attachments
            : [];

        // Keep the response hierarchy predictable: images first, then text,
        // then file/link lines, and finally QR codes.
        let html = renderFaqImages(attachments);

        html += allowSafeHTML(
            messageData.content || ''
        ).replace(
            /\n/g,
            "<br>"
        );

        html += `<div class="chat-faq-file-link-list">${renderFaqFilesAndLinks(attachments)}</div>`;
        html += renderFaqQRAttachments(attachments);

        if (data.feedback_log_id) {
            html += renderFaqFeedback(data.feedback_log_id);
        }

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
                        Send a ticket
                    </button>
                </div>
            `;

        }


        /*
         * Replace the typing indicator with the
         * chatbot's text response.
         */
        const bubble =
            setBotMessageContent(
                typingMessage,
                html
            );

        // QR pixels are rendered only after their containers exist in the DOM.
        renderFaqQRCodes(bubble);


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
         * Never expose provider/server failure text to the user. The backend
         * may include operational details in its JSON response for logging,
         * but the public chatbot should always show a stable recovery action.
         */
        setBotMessageContent(
            typingMessage,
            `Sorry, I couldn't process that question right now. Please try again.
                <div class="chat-fallback">
                    <button type="button" class="fallback-human-btn">Send a ticket</button>
                </div>`
        );

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
document.addEventListener("click", function (e) {
    const button = e.target.closest(".fallback-human-btn");
    if (!button) return;

    e.preventDefault();
    submitTicket(lastUserMessage, button);
});


/*
 * FAQ feedback uses delegated handlers because response bubbles are rendered
 * dynamically. Only the current authenticated user's exact answered log ID
 * is submitted; the server independently verifies ownership and FAQ linkage.
 */
document.addEventListener('click', async function (event) {
    const voteButton = event.target.closest('.chat-feedback-vote');
    if (voteButton) {
        const section = voteButton.closest('.chat-feedback');
        if (!section || section.dataset.submitting === '1') return;

        const rating = voteButton.dataset.rating;
        if (rating === 'not_helpful') {
            const form = section.querySelector('.chat-feedback-form');
            if (form) {
                form.hidden = false;
                form.querySelector('select')?.focus({ preventScroll: true });
            }
            section.querySelectorAll('.chat-feedback-vote').forEach((button) => {
                button.disabled = true;
            });
            return;
        }

        await submitFaqFeedback(section, 'helpful');
        return;
    }

    const cancelButton = event.target.closest('.chat-feedback-cancel');
    if (cancelButton) {
        const section = cancelButton.closest('.chat-feedback');
        if (!section) return;
        section.querySelector('.chat-feedback-form').hidden = true;
        section.querySelectorAll('.chat-feedback-vote').forEach((button) => {
            button.disabled = false;
        });
    }
});

document.addEventListener('submit', async function (event) {
    const form = event.target.closest('.chat-feedback-form');
    if (!form) return;
    event.preventDefault();

    const section = form.closest('.chat-feedback');
    if (!section) return;

    const submitButton = form.querySelector('.chat-feedback-submit');
    const reason = form.querySelector('[name="reason"]')?.value || '';
    const comment = form.querySelector('[name="comment"]')?.value || '';
    await submitFaqFeedback(section, 'not_helpful', { reason, comment }, submitButton);
});

async function submitFaqFeedback(section, rating, extra = {}, submitButton = null) {
    if (!section || section.dataset.submitting === '1') return;
    const logId = Number(section.dataset.feedbackLogId);
    if (!Number.isInteger(logId) || logId < 1) return;

    section.dataset.submitting = '1';
    const buttons = [...section.querySelectorAll('button')];
    buttons.forEach((button) => { button.disabled = true; });

    const originalText = submitButton?.textContent;
    if (submitButton) submitButton.textContent = 'Sending…';

    const status = section.querySelector('.chat-feedback-status');
    try {
        const response = await fetch(chatbotFeedbackUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                chatbot_log_id: logId,
                rating,
                reason: extra.reason || null,
                comment: extra.comment || null
            })
        });

        const data = await response.json().catch(() => ({}));
        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Feedback could not be saved. Please try again.');
        }

        section.querySelector('.chat-feedback-prompt').textContent = 'Thanks for helping us improve.';
        section.querySelector('.chat-feedback-actions')?.remove();
        section.querySelector('.chat-feedback-form')?.remove();
        if (status) {
            status.hidden = false;
            status.textContent = rating === 'helpful'
                ? 'Your positive feedback has been recorded.'
                : 'Your feedback has been sent to the FAQ team for review.';
        }
        section.dataset.submitting = '0';
        section.dataset.submitted = '1';
    } catch (error) {
        if (status) {
            status.hidden = false;
            status.textContent = error.message || 'Feedback could not be saved. Please try again.';
        }
        buttons.forEach((button) => { button.disabled = false; });
        if (submitButton && originalText) submitButton.textContent = originalText;
        section.dataset.submitting = '0';
    }
}


/*
 * =========================================================
 * CHATBOT OPEN / CLOSE CONTROLS
 * =========================================================
 *
 * The chatbot can be opened in two ways:
 *
 * 1. The user clicks the floating chatbot button.
 * 2. The user arrives at /map?open=chat from the
 *    landing page's "Ask a question" CTA.
 *
 * Both paths use the same openChat() function so the
 * chatbot's opening behavior stays centralized.
 */


/*
 * =========================================================
 * CHATBOT ELEMENTS
 * =========================================================
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
|--------------------------------------------------------------------------
| KNOWURLOCAL HELPDESK INTRO
|--------------------------------------------------------------------------
|
| The floating launcher briefly expands to introduce the Helpdesk.
|
| This is intentionally handled as a temporary UI state instead of
| modifying the actual chatbot-open state.
|
| That separation is important:
|
|     is-intro  → visual introduction
|     active    → chatbot is open
|
| They are two different states and should not depend on each other.
|
*/

const playHelpdeskIntro = () => {

    /*
     * Stop safely when the launcher does not exist.
     */
    if (!chatToggle) {
        return;
    }

    /*
     * Respect the user's operating-system reduced-motion
     * accessibility preference.
     */
    if (
        window.matchMedia(
            "(prefers-reduced-motion: reduce)"
        ).matches
    ) {
        return;
    }

    /*
     * Add the temporary visual state.
     *
     * CSS will handle the actual expansion, opacity,
     * and collapse animation.
     */
    chatToggle.classList.add(
        "is-helpdesk-intro"
    );

    /*
     * Remove the temporary state after the animation
     * has finished.
     *
     * The exact duration will match the CSS animation.
     */
    window.setTimeout(
        () => {

            chatToggle.classList.remove(
                "is-helpdesk-intro"
            );

        },
        4300
    );

};


/*
 * =========================================================
 * AGENCY CONTEXT
 * =========================================================
 *
 * The chatbot may optionally receive an agency ID/name
 * through data attributes.
 *
 * These values are still validated before being used.
 */

let agencyId =
    chatbot?.dataset.agency
        ? Number(
            chatbot.dataset.agency
        )
        : null;

/*
 * =========================================================
 * OPEN CHATBOT
 * =========================================================
 *
 * This function contains the complete opening behavior.
 *
 * Keeping this logic in one function prevents the normal
 * button click and URL-based opening from behaving
 * differently.
 */

function openChat(){

    /*
     * Stop safely if the chatbot elements are unavailable.
     */
    if(
        !chatbot ||
        !overlay
    ){
        return;
    }


    /*
     * Move the chatbot into its visible position.
     */
    chatbot.style.transform =
        "translateY(0)";


    /*
     * Activate the chatbot panel.
     */
    chatbot.classList.add(
        "active"
    );


    /*
     * Activate the background overlay.
     */
    overlay.classList.add(
        "active"
    );


    /*
     * Load suggestions only once during
     * the current page visit.
     */
    if(!greeted){

        loadSuggestions();

        greeted =
            true;

    }

}


/*
 * =========================================================
 * FLOATING CHAT BUTTON
 * =========================================================
 *
 * The existing chatbot icon continues to work exactly
 * as before, but now delegates to openChat().
 */

if (chatToggle) {

    chatToggle.addEventListener(
        "click",
        () => {

            /*
             * If the user interacts with the launcher during
             * the introduction, immediately remove the
             * temporary Helpdesk label.
             */
            chatToggle.classList.remove(
                "is-helpdesk-intro"
            );

            /*
             * Open the chatbot using the existing centralized
             * openChat() function.
             */
            openChat();

        }
    );

}

/*
|--------------------------------------------------------------------------
| Start Helpdesk introduction
|--------------------------------------------------------------------------
|
| A short delay gives the map interface time to render first.
| This makes the launcher feel intentionally introduced rather
| than appearing simultaneously with every other map element.
|
*/

window.setTimeout(
    playHelpdeskIntro,
    900
);


/*
 * =========================================================
 * LANDING PAGE CHAT REQUEST
 * =========================================================
 *
 * The landing page uses:
 *
 *     /map?open=chat
 *
 * Only the explicit "chat" value is accepted.
 *
 * This is intentionally allowlisted instead of accepting
 * arbitrary values from the URL.
 */

const urlParams =
    new URLSearchParams(
        window.location.search
    );

const requestedPanel =
    urlParams.get("open");


/*
 * Automatically open the chatbot when the user arrived
 * through the "Ask a question" CTA.
 */
if(
    requestedPanel === "chat"
){
    openChat();
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

        const imageButton =
            e.target.closest(".chat-faq-image-button");

        const image =
            e.target.closest(".clickable-image");

        const imageUrl =
            imageButton?.dataset.imageUrl ||
            image?.getAttribute("src") ||
            null;

        if(
            imageUrl &&
            isSafeUrl(imageUrl) &&
            imageModal &&
            modalImg
        ){

            modalImg.src = imageUrl;
            imageModal.classList.add("active");
            imageModal.setAttribute("aria-hidden", "false");

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
            imageModal.setAttribute("aria-hidden", "true");
            modalImg.removeAttribute("src");

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


if (askBtn) {
    askBtn.addEventListener("click", () => {
        submitTicket(messageInput?.value?.trim() || lastUserMessage, askBtn);
    });
}


/*
 * =========================================================
 * MOBILE SEND-A-TICKET BUTTON
 * =========================================================
 *
 * The mobile button reuses the existing ticket handler.
 *
 * This keeps the actual support-request logic in one place
 * instead of duplicating the API request here.
 */

const mobileAskBtn =
    document.getElementById("ask-human-mobile");


if (mobileAskBtn) {
    mobileAskBtn.addEventListener("click", () => {
        submitTicket(messageInput?.value?.trim() || lastUserMessage, mobileAskBtn);
    });
}