# Contributing to BuwisBuddy

BuwisBuddy computes Philippine taxes from rules encoded from the NIRC and BIR issuances. It was released without a review by a CPA, so the most useful help is checking those rules against the law. You don't need to write code to help.

## A rule looks wrong

Open an issue with the "A tax rule is wrong" template, or use "Report a mistake" in the app: on My tax ("Why is this my tax?"), the BIR rules page, or Your data. Say what the app does, what it should do, and the issuance that says so (RR, RMC or NIRC section, with a link if you have one).

Never include your name, TIN, address or your own figures in an issue.

## You're a CPA or tax lawyer

Thank you. [docs/RULE-REVIEW.md](docs/RULE-REVIEW.md) lists every rule as the app applies it, and [docs/REFERENCE-CASES.md](docs/REFERENCE-CASES.md) shows worked returns to check. Mark what you've checked and open an issue or pull request with your findings. A sign-off you give is recorded in the rule pack with your name, the date and what you checked it against (see "Updating the rule pack" in the [README](README.md)), and the app then shows that rule as reviewed.

## Changing the code

- Follow "Updating the rule pack" in the [README](README.md) for any rule change: cite the issuance, bump the versions, describe the change under `changes`, and add a test.
- A change to a figure on a return should come with the reference case it changes (`tests/Feature/Acceptance/ReferenceCasesTest.php`, then regenerate `docs/REFERENCE-CASES.md` as its header says).
- Run the checks in the README before opening a pull request.
- Keep the taxpayer's data on their computer: nothing about them leaves it except through the assistant, which they turn on themselves.

By contributing you agree that your contribution is released under the [MIT License](LICENSE).
