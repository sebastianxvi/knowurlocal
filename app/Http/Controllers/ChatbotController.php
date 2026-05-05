<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\ChatbotLog;
use App\Models\Faq;
use App\Models\SupportRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ChatbotController extends Controller
{
    /**
     * 🔐 SAFE LOGGING (never breaks system)
     */
    private function logChat($question, $reply, $agencyId = null, $type = null, $score = null)
    {
        try {
            ChatbotLog::create([
                'user_id' => auth()->id(),
                'question' => $question,
                'answer' => $reply,
                'agency_id' => $agencyId,
                'type' => $type,
                'score' => $score, // NEW
                'ip_address' => request()->ip(),
            ]);
        } catch (\Throwable $e) {}
    }

    /**
     * 🤖 AI REQUEST (used ONLY for classification)
     */
    private function askAI($messages)
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer '.env('OPENROUTER_API_KEY'),
            'Content-Type' => 'application/json',
        ])->post('https://openrouter.ai/api/v1/chat/completions', [
            "model" => "deepseek/deepseek-chat",
            "messages" => $messages,
            "temperature" => 0.3
        ])->json();
    }

    public function suggestions()
{
    // LIMIT to prevent abuse (security + performance)
    $faqs = \App\Models\Faq::select('question')
        ->whereNotNull('question')
        ->inRandomOrder()
        ->limit(10)
        ->get();

    return response()->json($faqs);
}


public function submitSupportRequest(Request $request)
{
    // 🔒 VALIDATION
    $validated = $request->validate([
        'question' => 'required|string|max:500', // 🔥 reduced limit
        'agency_id' => 'nullable|exists:agencies,id'
    ]);

    // 🛡️ BASIC SPAM PROTECTION (IP throttling)
    $recentCount = SupportRequest::where('ip_address', $request->ip())
        ->where('created_at', '>=', now()->subMinutes(1))
        ->count();

    if ($recentCount >= 3) {
        return response()->json([
            'success' => false,
            'message' => 'Too many requests. Please wait a moment.'
        ], 429);
    }

    // ✅ STORE REQUEST
    SupportRequest::create([
        'user_id' => auth()->id(), // null if guest
        'agency_id' => $validated['agency_id'] ?? null,
        'question' => $validated['question'],
        'ip_address' => $request->ip(),
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Your question has been sent to a human assistant.'
    ]);
}

    /**
     * 🧠 AI CLASSIFIER (YES / NO ONLY)
     */
    private function isRelevant($question)
    {
        $response = $this->askAI([
            [
                "role" => "system",
                "content" => "Answer ONLY YES or NO. Is the question related to government services, agencies, or NGOs?"
            ],
            [
                "role" => "user",
                "content" => $question
            ]
        ]);

        $reply = strtoupper(trim($response['choices'][0]['message']['content'] ?? ''));

        return str_contains($reply, 'YES');
    }

    /**
     * 🚀 MAIN FUNCTION
     */
    public function ask(Request $request)
    {
        // 🔒 VALIDATION
        $request->validate([
            'message' => 'required|string|max:1000'
        ]);

        $question = strtolower(trim($request->message));
        $agencyId = $request->agency_id;

            // 🧠 SIMPLE INTENT DETECTION (RUN FIRST)
        $greetings = ['hi','hello','hey','good morning','good afternoon','good evening'];
        $thanks = ['thanks','thank you','salamat'];

        foreach ($greetings as $word) {
            if (str_contains($question, $word)) {

                $reply = "Hello! How can I assist you today?";

                $this->logChat($question, $reply, $agencyId, 'greeting');

                return response()->json([
                    "choices" => [[
                        "message" => [
                            "content" => $reply
                        ]
                    ]]
                ]);
            }
        }

        foreach ($thanks as $word) {
            if (str_contains($question, $word)) {

                $reply = "You're welcome! Let me know if you need anything else.";

                $this->logChat($question, $reply, $agencyId, 'thanks');

                return response()->json([
                    "choices" => [[
                        "message" => [
                            "content" => $reply
                        ]
                    ]]
                ]);
            }
        }


        

        
        $cleanQuestion = preg_replace('/[^\w\s]/', '', $question);
        

        // 🔍 CURRENT AGENCY
        $currentAgency = $agencyId ? Agency::find($agencyId) : null;

        // 🔍 DETECT MENTIONED AGENCY
        $mentionedAgency = null;
        $rawQuestion = strtolower($request->message);

        foreach (Agency::all() as $agency) {

            $name = strtolower($agency->agency_name);

            if (str_contains($rawQuestion, $name)) {
                $mentionedAgency = $agency;
                break;
            }

            foreach (explode(' ', $name) as $word) {
                if (strlen($word) >= 4 && str_contains($rawQuestion, $word)) {
                    $mentionedAgency = $agency;
                    break 2;
                }
            }
        }

        // ❗ WRONG AGENCY
        if ($agencyId && $mentionedAgency && $mentionedAgency->id != $agencyId) {

            $reply = "It looks like your question is about {$mentionedAgency->agency_name}.

You are currently chatting with {$currentAgency->agency_name}.

Please visit {$mentionedAgency->agency_name} for accurate information.";

            $this->logChat($question, $reply, $agencyId, 'wrong_agency');

            return response()->json([
                "choices" => [[
                    "message" => ["content" => $reply]
                ]]
            ]);
        }

        // 🔍 KEYWORDS
        $stopWords = ['how','do','i','what','is','the','for','a','an','to','of','in'];
        $words = array_diff(explode(' ', $cleanQuestion), $stopWords);

        // 🔍 FAQ QUERY
        $query = Faq::query();

        if ($agencyId) {
            $query->where('agency_id', $agencyId);
        }

        $query->where(function ($q) use ($words) {
            foreach ($words as $word) {
                $q->orWhere('question', 'like', "%$word%")
                  ->orWhere('keywords', 'like', "%$word%");
            }
        });

        $faqs = $query->limit(5)->get();

        // 🔥 SECOND SEARCH: check other agencies if none found
        if ($faqs->isEmpty()) {

            $globalFaqs = Faq::where(function($q) use ($words) {
                foreach ($words as $word) {
                    $q->orWhere('question', 'like', "%$word%")
                    ->orWhere('keywords', 'like', "%$word%");
                }
            })->limit(5)->get();

            // if found in another agency
            if (!$globalFaqs->isEmpty()) {

                $otherFaq = $globalFaqs->first();
                $otherAgency = $otherFaq->agency;

                $reply = "It looks like your question is about {$otherAgency->agency_name}.

        You are currently viewing another agency.

        Please go to {$otherAgency->agency_name} page to get accurate information.";

                $this->logChat($question, $reply, $agencyId, 'wrong_agency');

                return response()->json([
                    "choices" => [[
                        "message" => ["content" => $reply]
                    ]]
                ]);
            }
        }

        // 🔥 FALLBACK
        if ($faqs->isEmpty()) {
            $faqs = $agencyId
                ? Faq::where('agency_id', $agencyId)->limit(5)->get()
                : Faq::limit(5)->get();
        }

        // 🔍 SCORE MATCH
        $bestFaq = null;
        $bestScore = 0;

        // 🔍 SCORE FAQS (ONLY ONCE)
        $weakWords = [
            'police','dilg','dti','agency','office','government',
            'information','service'
        ];

        $scoredFaqs = [];
        $bestFaq = null;
        $bestScore = 0;

        foreach ($faqs as $faq) {

            $score = 0;

            // ✅ boost if agency matches
            if ($mentionedAgency && $faq->agency_id == $mentionedAgency->id) {
                $score += 5;
            }

            foreach ($words as $word) {

                if (in_array($word, $weakWords)) continue;

                if (str_contains(strtolower($faq->question), $word)) $score += 2;
                if (str_contains(strtolower($faq->keywords ?? ''), $word)) $score += 2;
            }

            $scoredFaqs[] = [
                'faq' => $faq,
                'score' => $score
            ];

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestFaq = $faq;
            }
        }

        // 🔥 THRESHOLD (prevents random answers)
        $MIN_SCORE = 3;

        if ($bestScore < $MIN_SCORE) {
            $bestFaq = null;
        }

        // 🔍 FIND SIMILAR MATCHES
        $similarFaqs = [];

        foreach ($scoredFaqs as $item) {
            if ($bestScore >= $MIN_SCORE && $item['score'] == $bestScore) {
                $similarFaqs[] = $item['faq'];
            }
        }

        // 🔥 GLOBAL MODE → SHOW OPTIONS FIRST
        if (count($similarFaqs) > 1 && !$agencyId && $bestScore >= $MIN_SCORE) {

            $options = "";
            $count = 0;

            foreach ($similarFaqs as $faq) {

                $options .= "• ".$faq->agency->agency_name." – ".$faq->question."\n";

                if (++$count == 3) break;
            }

            $reply = "I found multiple related questions. Did you mean:\n\n".$options;

            $this->logChat($question, 'Multiple options shown', $agencyId, 'options');

            return response()->json([
                "choices" => [[
                    "message" => ["content" => $reply]
                ]]
            ]);
        }

        // ✅ SINGLE BEST MATCH
        // ✅ SINGLE BEST MATCH
if ($bestFaq) {

    $reply = $bestFaq->answer;

    $maxPossible = count($words) * 2 + 5; // keywords + agency boost

    $normalizedScore = $maxPossible > 0 
        ? $bestScore / $maxPossible 
        : 0;

    $this->logChat($question, $reply, $agencyId, 'faq', $normalizedScore);

    return response()->json([
        "choices" => [[
            "message" => [
                "content" => $reply,

                // ✅ NEW: include image if exists
                "image" => $bestFaq->image 
                    ? asset('storage/' . $bestFaq->image) 
                    : null
            ]
        ]]
    ]);
}

        // 🤖 AI CLASSIFICATION (ONLY HERE)
        if (!$this->isRelevant($question)) {

            $reply = "Sorry, this question is outside the scope of KNOWURLOCAL.";

            $this->logChat($question, $reply, $agencyId, 'irrelevant');

            return response()->json([
                "choices" => [[
                    "message" => ["content" => $reply]
                ]]
            ]);
        }

        // 📩 FINAL FALLBACK
        $reply = "I couldn’t find an exact answer for your question.<br><br>
        <button class='fallback-human-btn'>Send to human</button>";

        $this->logChat($question, $reply, $agencyId, 'fallback');

        return response()->json([
            "choices" => [[
                "message" => ["content" => $reply]
            ]]
        ]);
    }
}