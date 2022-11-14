# Why the fork?

The original Learning With Texts code is ... tough.  Per the author at https://learning-with-texts.sourceforge.io/:

> My programming style is quite chaotic, and my software is mostly undocumented.

Regardless, the original LWT author released a _super_ idea and a working project!

Hugo Fara then picked up the project and started working at it in earnest in [his fork](https://github.com/HugoFara/lwt), making massive improvements to the code structure.

**Thanks to both of these guys!**

I started using LWT in Oct/Nov 2022.  As a (former-ish) dev, there were some things in the code that _really bothered_ me, and potentially blocked me from adding features I wanted for myself.  I proposed some patches, but Hugo said he was more interested in writing a new version of LWT, incorporating things he'd learned while working on the project.

Since Hugo's fork of LWT _works well_ for me, I'm continuing with his work, making the changes that I feel are necessary to the project.  These changes may or may not make it back upstream -- I hope they do, as I would rather contribute to a public project -- so we'll see how it shakes out.

My current ideas of necessary changes for the code health and my sanity:

* add test coverage for database calls
* remove the current "table prefixing" used for multi-user LWT installations
* fix database migrations and management

And then some potential new features:

* word groups
* support for "canonical/root forms" - e.g. declensions can/should be linked back to "parent terms"

There are other changes that could happen, but they might be too much work.  I'm more interested in learning languages than at hacking at code -- even though hacking can be extremely satisfying -- so I'll be weighing the time I invest, and hopefully I'll be able to contribute any fixes I make to Hugo's future efforts.