/**
 * Relative dates as a reader actually meets them.
 *
 * This plugin has no settings and no admin screen: it filters the date and time
 * a theme prints, and offers two shortcodes. So the only place its behaviour is
 * visible is a rendered page -- and the filters are the risky half, because they
 * run at priority 999 on hooks a theme, a block and a comment list all reach
 * through different call paths.
 */

const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );

/**
 * A title no earlier run can have used.
 *
 * @param {string} base What the title should say.
 * @return {string} That, plus enough to tell this run from the last.
 */
function uniqueTitle( base ) {
	return `${ base } ${ Date.now().toString( 36 ) }`;
}

/**
 * How far the site's wall clock is from this machine's, in milliseconds.
 *
 * Measured once in beforeAll rather than assumed. See siteNow().
 *
 * @type {number}
 */
let siteSkewMs = 0;

/**
 * Format a datetime the way the REST date field wants it.
 *
 * No timezone suffix, because the field has no timezone: WordPress reads it as
 * site local time. So toISOString() is exactly wrong here -- it would convert
 * to UTC and hand the site a wall clock reading that means something else.
 *
 * @param {Date} when The moment, already expressed as a site wall clock.
 * @return {string} A Y-m-d\TH:i:s datetime.
 */
function asMysqlDatetime( when ) {
	const pad = ( n ) => String( n ).padStart( 2, '0' );

	return (
		when.getFullYear() +
		'-' +
		pad( when.getMonth() + 1 ) +
		'-' +
		pad( when.getDate() ) +
		'T' +
		pad( when.getHours() ) +
		':' +
		pad( when.getMinutes() ) +
		':' +
		pad( when.getSeconds() )
	);
}

/**
 * Now, on the site's clock rather than this machine's.
 *
 * Every phrase this plugin produces is measured from current_datetime(), and
 * every date it is given is a site local one. This machine is not the site: a
 * developer here runs at UTC+8 while wp-env runs at UTC, so a post dated from
 * local parts arrives eight hours out. Midday hides that for the day-counting
 * tests, and cannot hide it for a five minute one -- a post meant to be five
 * minutes old was stored eight hours in the future, where the plugin correctly
 * refused to say anything relative at all.
 *
 * The skew is not derived from a timezone name or an offset setting, both of
 * which then have to be reasoned about across DST. It is measured: publish
 * something, ask the site what time it thinks that was, and keep the
 * difference. The returned Date reads, in this machine's local time, as the
 * site's wall clock.
 *
 * @return {Date} Site-local now.
 */
function siteNow() {
	return new Date( Date.now() + siteSkewMs );
}

/**
 * A datetime a whole number of days back, at midday, on the site's clock.
 *
 * Not `Date.now() - days * 24 * 60 * 60 * 1000`. That is an offset in hours,
 * and every phrase this plugin produces is about calendar days: a 26 hour
 * offset is "yesterday" only when the clock is past 02:00, and CI ran the suite
 * at 01:44 UTC, where it landed two days back. The plugin said "2 days ago",
 * which was correct, and the test failed. It had always passed here because
 * nobody runs a suite at two in the morning.
 *
 * Midday because it is the furthest point from a day boundary, so the hour the
 * suite runs at cannot move the date onto another day. That is now the only
 * job it does: the timezone half of the problem belongs to siteNow().
 *
 * @param {number} days How many days back.
 * @return {string} A Y-m-d\TH:i:s datetime.
 */
function daysAgo( days ) {
	const when = siteNow();

	when.setHours( 12, 0, 0, 0 );
	when.setDate( when.getDate() - days );

	return asMysqlDatetime( when );
}

/**
 * A datetime a number of minutes back, on the site's clock.
 *
 * @param {number} minutes How many minutes back.
 * @return {string} A Y-m-d\TH:i:s datetime.
 */
function minutesAgo( minutes ) {
	return asMysqlDatetime( new Date( siteNow().getTime() - ( minutes * 60 * 1000 ) ) );
}

/**
 * The calendar year a given number of days back.
 *
 * @param {number} days How many days back.
 * @return {string} The year, as it appears on the page.
 */
function yearOf( days ) {
	return daysAgo( days ).slice( 0, 4 );
}

/**
 * Publish a post and open it.
 *
 * @param {Object}                          requestUtils The e2e request helper.
 * @param {import('@playwright/test').Page} page         Page under test.
 * @param {Object}                          fields       Post fields to merge in.
 * @return {Promise<Object>} The created post.
 */
async function openPost( requestUtils, page, fields = {} ) {
	const post = await requestUtils.createPost( {
		title: uniqueTitle( 'Relative' ),
		content: 'Body.',
		status: 'publish',
		...fields,
	} );

	await page.goto( post.link );

	return post;
}

test.describe( 'Relative dates on the front end', () => {
	test.beforeAll( async ( { requestUtils } ) => {
		// Publish something with no date and read back the one WordPress
		// stamped on it. That is the site's wall clock, straight from the site,
		// and the gap from this machine's is every timezone question this suite
		// has to answer. Parsing both as if they were UTC compares the two wall
		// clock readings rather than the two instants, which is the thing we
		// want the difference of.
		const probe = await requestUtils.createPost( {
			title: 'Clock probe',
			content: 'Discarded.',
			status: 'publish',
		} );

		const siteWallClock = Date.parse( probe.date + 'Z' );
		const hostWallClock = Date.now() - ( new Date().getTimezoneOffset() * 60 * 1000 );

		siteSkewMs = siteWallClock - hostWallClock;

		await requestUtils.deleteAllPosts();
	} );

	test( 'a post published just now reads as Today', async ( { page, requestUtils } ) => {
		await openPost( requestUtils, page );

		// The theme prints the date through get_the_date, which this plugin
		// filters. "Today" rather than a date is the whole point of the plugin,
		// and it is what a theme calling the_date() gets without changing a line.
		await expect( page.locator( '.entry-content, article' ).first() ).toBeVisible();
		await expect( page.locator( 'body' ) ).toContainText( 'Today' );
	} );

	test( 'a post from last week reads as days ago, beside its date', async ( {
		page,
		requestUtils,
	} ) => {
		await openPost( requestUtils, page, { date: daysAgo( 3 ) } );

		// Not "Today", and not a bare date either: the default keeps the
		// formatted date and puts the relative phrase after it in brackets, so a
		// reader can still see exactly when.
		await expect( page.locator( 'body' ) ).toContainText( /3 days ago/ );
	} );

	test( 'yesterday is named rather than counted', async ( { page, requestUtils } ) => {
		await openPost( requestUtils, page, { date: daysAgo( 1 ) } );

		await expect( page.locator( 'body' ) ).toContainText( 'Yesterday' );
	} );

	test( 'a date in an earlier year is left alone', async ( { page, requestUtils } ) => {
		await openPost( requestUtils, page, { date: daysAgo( 800 ) } );

		// "114 weeks ago" tells a reader nothing they wanted to know, so a post
		// from a different calendar year keeps its date and says nothing else.
		await expect( page.locator( 'body' ) ).not.toContainText( 'weeks ago' );
		await expect( page.locator( 'body' ) ).toContainText( yearOf( 800 ) );
	} );

	test( 'the shortcode prints the same phrase inside content', async ( {
		page,
		requestUtils,
	} ) => {
		await openPost( requestUtils, page, {
			content: 'Posted: [relativedate]',
		} );

		await expect( page.locator( '.entry-content' ) ).toContainText( 'Posted: ' );
		await expect( page.locator( '.entry-content' ) ).toContainText( 'Today' );
	} );

	test( 'ago_only drops the formatted date', async ( { page, requestUtils } ) => {
		await openPost( requestUtils, page, {
			date: daysAgo( 3 ),
			content: 'A: [relativedate] B: [relativedate ago_only="true"]',
		} );

		const content = await page.locator( '.entry-content' ).innerText();

		const [ , withDate, agoOnly ] = content.match( /A:\s*(.*?)\s*B:\s*(.*)/s );

		expect( withDate ).toContain( '3 days ago' );
		expect( agoOnly ).toContain( '3 days ago' );

		// The bracketed form carries the date as well; ago_only is only the
		// phrase, so it is the shorter of the two.
		expect( agoOnly.length ).toBeLessThan( withDate.length );
		expect( agoOnly ).not.toContain( '(' );
	} );

	test( 'the time shortcode counts in minutes, not days', async ( { page, requestUtils } ) => {
		// Minutes, so this one really is an elapsed-time offset rather than a
		// calendar day. It is also the test with no slack in it: the day tests
		// can survive a few hours of clock difference between this machine and
		// the site, and five minutes cannot.
		await openPost( requestUtils, page, {
			date: minutesAgo( 5 ),
			content: 'At [relativetime]',
		} );

		// Never a negative count: before 2.0.0 a "less than" ladder let a time
		// inside the first bucket fall through and render "(-300 seconds ago)".
		await expect( page.locator( '.entry-content' ) ).toContainText( /minutes? ago/ );
		await expect( page.locator( '.entry-content' ) ).not.toContainText( '-' );
	} );

	test( 'a comment carries a relative date of its own', async ( { page, requestUtils } ) => {
		const post = await requestUtils.createPost( {
			title: uniqueTitle( 'Commented' ),
			content: 'Body.',
			status: 'publish',
		} );

		await requestUtils.rest( {
			method: 'POST',
			path: '/wp/v2/comments',
			data: {
				post: post.id,
				content: 'A comment worth dating',
				author_name: 'Visitor',
				author_email: 'visitor@example.com',
				status: 'approved',
			},
		} );

		await page.goto( post.link );

		const comment = page.locator( '.comment-body, .comment' ).first();

		await expect( comment ).toContainText( 'A comment worth dating' );

		// get_comment_date is a different hook from get_the_date, reached through
		// a different call path, and it is the one that used to be missed.
		await expect( comment ).toContainText( 'Today' );
	} );

	test( 'the archive listing gets relative dates too', async ( { page, requestUtils } ) => {
		await requestUtils.createPost( {
			title: uniqueTitle( 'In the archive' ),
			content: 'Body.',
			status: 'publish',
		} );

		await page.goto( '/' );

		// The home page is a different loop from a single post, and the filter
		// runs on both because it is on the hook rather than on the template.
		await expect( page.locator( 'body' ) ).toContainText( 'Today' );
	} );

	test( 'the plugin adds nothing to wp-admin', async ( { page, admin } ) => {
		await admin.visitAdminPage( 'index.php' );

		// No settings, no screen, no menu entry: there is nothing to configure,
		// and a plugin that adds a menu to say so is a plugin adding a menu.
		await expect(
			page.locator( '#adminmenu' ).getByText( 'RelativeDate', { exact: false } ),
		).toHaveCount( 0 );
	} );
} );
