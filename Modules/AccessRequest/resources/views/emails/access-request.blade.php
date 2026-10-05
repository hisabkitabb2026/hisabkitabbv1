<x-mail::message>
# Permission request

**{{ $requesterName }}** is asking for access in **{{ $companyName }}**:

---

{{ $message }}

---

<x-mail::subcopy>
Grant it under Settings &rarr; Roles &rarr; the member's role.
</x-mail::subcopy>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
