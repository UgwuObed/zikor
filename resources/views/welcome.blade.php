<form action="/api/interact-with-ai" method="POST">
    @csrf
    <input type="text" name="user_input" placeholder="Type your message here...">
    <button type="submit">Send</button>
</form>

<div id="ai-response">
    <!-- AI response will be displayed here -->
</div>
