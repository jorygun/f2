<h4>Assets</h4>
        <p>"Assets" are the various pictures, documents, and audio/video items that
have been uploaded to the site over the years.  The assets have been (mostly)
indexed in a database for easy search and retrieval.  Use <a href='/asset_search.php' target='asearch'>this page</a> to
find and retrieve an asset.</p>
<p>Graphic assets are stored at the original resolution and also one or more fixed size 'thumbnails'.  Thumbnails are used to display the asset in news articles and elsewhere.  Clicking on a thumbnail retrieves the original image. .</p>

<p>Assets have various attributes, including a title, caption, vintage, and keywords,
a "source" - the original document referenced, and a thumbnail image - a small graphic to represent the source.
</p>



<table class='bordered row-lines'>
<tr><td>Search Terms (on asset search screen)</td><td>
	Text strings to look for in the title, caption, or keywords of an asset.  Not case sensitive.  String may include a space, like "John East".  Separate multiples strings with commas; they are ORed in the search.</td></tr>

<tr><td>Vintage</td><td>
	Estimated year the item was created in.  In search, you can choose to set a range +/- years. Some items do not have a vintage.</td></tr>

<tr><td>Asset Type</td><td>
	This is automatically set based on the type (mime-type) of the asset.</td></tr>

<tr><td>Tags</td><td>
	Tags are another way to categorize an asset.  The ones with * on them are considered "archival".  Tags are completely optional.</td></tr>

<tr><td>Asset ID or Range</td><td>
	Every asset has an id number.  You can search for a single asset or a list of assets or a range of two ids separated by a hyphen (-). </td></tr>

<tr><td>Status</td><td>
	Status is set automatically by other processes.  Active includes New, Unreviewed, OK, and Error Accepted.  The latter has something wrong with the source url, usually.</td></tr>

<tr><td>Contributor</td><td>
	The Flames member who contributed the asset</td></tr>

<tr><td>First Use</td><td>
	When the asset is published, the date and location are recorded.  You can search for a date.</td></tr>
<tr><td>Source URL</td><td>This is the url of the document this asset represents.  It can be a local file ('/assets/files/4566.php') or a remote web page. (Remote web pages may not be reliable over a long term.)  Youtube videos are often used.</td></tr>
<tr><td>Thumb URL</td><td>
	Normally the thumbnail image for the asset is created automatically from the source document.  The always works for images, pdf files, and youtube videos.  For other documents, a generic icon might be used.  But it is always possible for the uploader to specify another source for the thumbnail, for example and another graphic can be specified, or a graphic file uploaded.  The Thumb URL should always be stored locally, not a remote web page.
</table>

