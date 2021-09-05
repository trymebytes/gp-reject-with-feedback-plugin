# gp-reject-with-feedback-plugin
This plugin allows users to add a feedback when rejecting a GlotPress translation

#How it works
- When the "Reject" button is clicked, translator is required to enter the reason for rejection and submit
- A forum is created for the translation locale if it doesn't exist yet
- A forum topic is created with the reason for rejection set as the the topic content
- Other rejections for same translation are added as a reply to the initial topic

Incomplete features
- Plugin unit tests
- Handle when a forum is deleted
- Handle when a forum is already created by admin with same name for a locale yet to have a single rejected translation(hence, no rejection forum and topics)