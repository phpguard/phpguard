Feature: Developer configure PhpGuard
    As Developer
    I should able to configure PhpGuard
    In order to get feedback on a state of my application

    Scenario: Using custom configuration file
        Given the file "foobar.yml" contains:
              """
              behat:
                  watch:
                      - { pattern: "#^features/(.+)\.feature$#" }
              """
         When I start phpguard with "--config=foobar.yml -vvv"
         Then I should see "Plugin Behat activated"
