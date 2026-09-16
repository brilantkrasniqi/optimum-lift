# Hosting and deployment path

Type: task
Status: open
Blocked by: 03

## Question

Where does the site live, and how does it get there?

WordPress development experience is present, but a site has **never been deployed** before. So this ticket is as much about a repeatable, understood process as it is about picking a host: hosting choice, DNS, SSL, staging vs production, and how code gets from the repo to the server.

Options span managed WordPress hosting, a VPS administered directly, and cheap shared hosting.

## Definition of done

A live, empty WordPress install on the real domain, over HTTPS, with a written record of how to deploy to it again.

## Notes

HITL, and a strong fit for `/wizard` — it generates an interactive script that opens each URL, captures each value, and writes credentials where they belong, so the procedure stops being something to re-explain every time.

Recommendation to test, not assume: **managed WordPress hosting in the EU.** A VPS is within reach skill-wise, but the stated goal is to spend time on agentic coding, not on patching servers. EU hosting also keeps GDPR straightforward once email capture starts.

Blocked on 03 because if the platform decision moves away from WordPress, this ticket changes entirely.
