# MidCamp 2025 Simple Instructions
* Make sure you already have ddev and npm (node v18+) installed, a docker container running, and you are in the correct project directory
* Run:
ddev config --project-type=drupal11 --docroot=web
ddev start
ddev composer install
ddev launch

* On "Get Started", you can "Skip this step"
* Give your site a name, and set an admin email address and password that you will easily remember.
* After "Setting up your site", log in with the admin email address and password that you just set.
* Go to /admin/modules, find "Find a Doctor", check the box, scroll down and click "Install"
* Go to /admin/structure/block, and click "Place block" in the "Content" area. Look for "Find a Doctor block", and click "Place block". Then un-check "Display title" and click "Save block".
* Go to /admin/config/development/performance, and turn off "Aggregate CSS files" and "Aggregate JavaScript files"
* View your homepage in a browser - the block should not appear yet
* Run these commands:
cd web/modules/custom/find_a_doctor/find_a_doctor
npm install
npm run watch
* Reload your homepage - see the "Vite + React" block displayed
* Make a non-breaking edit to web/modules/custom/find_a_doctor/find_a_doctor/src/App.tsx, save the file, and then reload your homepage.
* If nothing went wrong, hopefully you can now enjoy the sweet smell of victory!

-----------------

# Drupal CMS

Drupal CMS is a fast-moving open source product that enables site builders to easily create new Drupal sites and extend them with smart defaults, all using their browser.

## Getting started

If you want to use [DDEV](https://ddev.com) to run Drupal CMS locally, follow these instructions:

1. Install DDEV following the [documentation](https://ddev.com/get-started/)
2. Open the command line and `cd` to the root directory of this project
3. Run the following commands:
```shell
ddev config --project-type=drupal11 --docroot=web
ddev start
ddev composer install
ddev launch
```

Drupal CMS has the same system requirements as Drupal core, so you can use your preferred setup to run it locally. [See the Drupal User Guide for more information](https://www.drupal.org/docs/user_guide/en/installation-chapter.html) on how to set up Drupal.

### Installation options

The Drupal CMS installer offers a list of features preconfigured with smart defaults. You will be able to customize whatever you choose, and add additional features, once you are logged in.

After the installer is complete, you will land on the dashboard.

## Documentation

Coming soon ... [We're working on Drupal CMS specific documentation](https://www.drupal.org/project/drupal_cms/issues/3454527).

In the meantime, learn more about managing a Drupal-based application in the [Drupal User Guide](https://www.drupal.org/docs/user_guide/en/index.html).

## Contributing

Drupal CMS is developed in the open on [Drupal.org](https://www.drupal.org). We are grateful to the community for reporting bugs and contributing fixes and improvements.

[Report issues in the queue](https://drupal.org/node/add/project-issue/drupal_cms), providing as much detail as you can. You can also join the #drupal-cms-support channel in the [Drupal Slack community](https://www.drupal.org/slack).

Drupal CMS has adopted a [code of conduct](https://www.drupal.org/dcoc) that we expect all participants to adhere to.

To contribute to Drupal CMS development, see the [drupal_cms project](https://www.drupal.org/project/drupal_cms).

## License

Drupal CMS and all derivative works are licensed under the [GNU General Public License, version 2 or later](http://www.gnu.org/licenses/old-licenses/gpl-2.0.html).

Learn about the [Drupal trademark and logo policy here](https://www.drupal.com/trademark).
