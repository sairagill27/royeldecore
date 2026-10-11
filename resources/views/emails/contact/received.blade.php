New message from the contact form

Name: {!! $contact->name !!}
Email: {!! $contact->email !!}
Subject: {!! $contact->subject !!}

{!! $contact->message !!}

Open it in the admin panel:
{{ route('admin.messages.show', $contact) }}